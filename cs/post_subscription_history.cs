using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net.Http;
using System.Text;
using LiteDB;
using Newtonsoft.Json;

public class CPHInline
{
    // ===== CONFIGURATION =====
    private const string API_BASE_URL = "https://gargoyled-gearldine-interfamily.ngrok-free.dev";
    private string API_TOKEN;
    private const string LOG_FILE_PATH = "logs/subscription_history_poster.log";
    private const string DB_PATH = "data/twitch_data.db";
    // ===== HTTP CLIENT =====
    private static readonly HttpClient _httpClient = new HttpClient
    {
        Timeout = TimeSpan.FromSeconds(30),
    };
    /// <summary>
    /// Custom log function that writes to a specific log file
    /// </summary>
    private void WriteLog(string level, string message)
    {
        try
        {
            string timestamp = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss");
            string logEntry = $"[{timestamp}] [{level}] {message}";
            // Ensure the logs directory exists
            string logDirectory = Path.GetDirectoryName(LOG_FILE_PATH);
            if (!string.IsNullOrEmpty(logDirectory) && !Directory.Exists(logDirectory))
            {
                Directory.CreateDirectory(logDirectory);
            }

            // Append to log file
            File.AppendAllText(LOG_FILE_PATH, logEntry + Environment.NewLine);
        }
        catch (Exception ex)
        {
            // Fallback to CPH logging if file writing fails
            CPH.LogError($"Failed to write to auto-purge log: {ex.Message}");
        }
    }

    public void Init()
    {
        // Ensure we are working with a clean slate
        _httpClient.DefaultRequestHeaders.Clear();
        // Get API token from global var
        API_TOKEN = CPH.GetGlobalVar<string>("laravelApiKey", true);
        // Add API token for authentication
        _httpClient.DefaultRequestHeaders.Add("Authorization", $"Bearer {API_TOKEN}");
        _httpClient.DefaultRequestHeaders.Add("Accept", "application/json");
        _httpClient.DefaultRequestHeaders.Add("User-Agent", "StreamerBot-SubscriptionHistory/1.0");
    }

    public void Dispose()
    {
        // Free up allocations
        _httpClient.Dispose();
    }

    public bool Execute()
    {
        return true;
    }

    public bool PostSubscriptionHistoryRecent()
    {
        try
        {
            WriteLog("INFO", "Starting recent subscription history sync");
            // Check if database file exists
            if (!File.Exists(DB_PATH))
            {
                WriteLog("ERROR", $"Database file not found at {DB_PATH}");
                CPH.SendMessage("Error: Database file not found");
                return false;
            }

            // Calculate time window (last 5 minutes)
            DateTime fiveMinutesAgo = DateTime.UtcNow.AddMinutes(-5);
            // Query LiteDB for all recent subscription history (no user filtering)
            var subscriptionHistory = QueryRecentSubscriptionHistory(fiveMinutesAgo);
            if (subscriptionHistory == null || subscriptionHistory.Count == 0)
            {
                WriteLog("ERROR", "No subscription history found in the last 5 minutes");
                CPH.SendMessage("No subscription history record found in the last 5 minutes");
                return false;
            }

            WriteLog("INFO", $"Found {subscriptionHistory.Count} subscription history record(s)");
            // Post to Laravel API
            bool success = PostToApi(subscriptionHistory);
            if (success)
            {
                WriteLog("INFO", $"✓ Recent subscription history sync completed - {subscriptionHistory.Count} record(s)");
            }
            else
            {
                WriteLog("ERROR", "✗ Recent subscription history sync failed");
            }

            return success;
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"PostSubscriptionHistoryRecent error: {ex.Message} | Inner: {ex.InnerException?.Message ?? "None"} | StackTrace: {ex.StackTrace}");
            CPH.SendMessage($"Error syncing subscription history: {ex.Message}");
            return false;
        }
    }

    public bool PostSubscriptionHistoryBulk()
    {
        try
        {
            WriteLog("INFO", "Starting subscription history sync");
            // Check if database file exists
            if (!File.Exists(DB_PATH))
            {
                WriteLog("ERROR", $"Database file not found at {DB_PATH}");
                return false;
            }

            // Read subscription history from LiteDB
            var subscriptionHistory = ReadSubscriptionHistoryFromDb();
            if (subscriptionHistory == null || subscriptionHistory.Count == 0)
            {
                WriteLog("WARN", "No subscription history records found in database");
                return true; // Not an error, just no data
            }

            WriteLog("INFO", $"Found {subscriptionHistory.Count} subscription history records");
            // Post to Laravel API
            bool success = PostToApi(subscriptionHistory);
            if (success)
            {
                WriteLog("INFO", $"✓ Subscription history sync completed - {subscriptionHistory.Count} records");
            }
            else
            {
                WriteLog("ERROR", "✗ Subscription history sync failed");
            }

            return success;
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"Execute error: {ex.Message} | Inner: {ex.InnerException?.Message ?? "None"} | StackTrace: {ex.StackTrace}");
            return false;
        }
    }

    /// <summary>
    /// Query recent subscription history from LiteDB database (last 5 minutes)
    /// </summary>
    private List<object> QueryRecentSubscriptionHistory(DateTime fiveMinutesAgo)
    {
        try
        {
            var subscriptionHistory = new List<object>();
            // Open LiteDB in read-only mode
            var connectionString = $"Filename={DB_PATH};ReadOnly=true";
            using var db = new LiteDatabase(connectionString);
            // Get the subscriptionHistory collection
            var collection = db.GetCollection("subscriptionHistory");
            // Build query: subscribedAt >= fiveMinutesAgo (all records newer than 5 minutes old)
            var query = Query.GTE("subscribedAt", fiveMinutesAgo);
            var results = collection.Find(query).ToList();
            foreach (var doc in results)
            {
                // Extract _id (ObjectId)
                BsonValue idValue = doc["_id"];
                string oid = idValue.IsObjectId ? idValue.AsObjectId.ToString() : idValue.ToString();
                // Extract subscribedAt (DateTime)
                BsonValue subscribedAtValue = doc["subscribedAt"];
                string subscribedAtDate = null;
                if (subscribedAtValue.IsDateTime)
                {
                    subscribedAtDate = subscribedAtValue.AsDateTime.ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ss.fffZ");
                }
                else if (subscribedAtValue.IsString)
                {
                    subscribedAtDate = subscribedAtValue.AsString;
                }

                // Extract userId and gifterUserId
                string userId = doc.ContainsKey("userId") ? doc["userId"].ToString() : null;
                string gifterUserIdFromDb = doc.ContainsKey("gifterUserId") ? doc["gifterUserId"].ToString() : null;
                // Skip records without required fields
                if (string.IsNullOrEmpty(oid) || string.IsNullOrEmpty(subscribedAtDate) || string.IsNullOrEmpty(userId) || string.IsNullOrEmpty(gifterUserIdFromDb))
                {
                    WriteLog("WARN", $"Skipping record with missing required fields: oid={oid}, userId={userId}, gifterUserId={gifterUserIdFromDb}");
                    continue;
                }

                // Transform to Laravel API format
                // Use Dictionary to handle $oid and $date property names
                var record = new Dictionary<string, object>
                {
                    ["_id"] = new Dictionary<string, object>
                    {
                        ["$oid"] = oid
                    },
                    ["subscribedAt"] = new Dictionary<string, object>
                    {
                        ["$date"] = subscribedAtDate
                    },
                    ["userId"] = userId,
                    ["gifterUserId"] = gifterUserIdFromDb
                };
                subscriptionHistory.Add(record);
            }

            return subscriptionHistory;
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"QueryRecentSubscriptionHistory error: {ex.Message}");
            return null;
        }
    }

    /// <summary>
    /// Read subscription history from LiteDB database
    /// </summary>
    private List<object> ReadSubscriptionHistoryFromDb()
    {
        try
        {
            var subscriptionHistory = new List<object>();
            // Open LiteDB in read-only mode
            var connectionString = $"Filename={DB_PATH};ReadOnly=true";
            using var db = new LiteDatabase(connectionString);
            // Get the subscriptionHistory collection
            var collection = db.GetCollection("subscriptionHistory");
            // Query all records (or filter as needed)
            var results = collection.FindAll().ToList();
            foreach (var doc in results)
            {
                // Extract _id (ObjectId)
                BsonValue idValue = doc["_id"];
                string oid = idValue.IsObjectId ? idValue.AsObjectId.ToString() : idValue.ToString();
                // Extract subscribedAt (DateTime)
                BsonValue subscribedAtValue = doc["subscribedAt"];
                string subscribedAtDate = null;
                if (subscribedAtValue.IsDateTime)
                {
                    subscribedAtDate = subscribedAtValue.AsDateTime.ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ss.fffZ");
                }
                else if (subscribedAtValue.IsString)
                {
                    subscribedAtDate = subscribedAtValue.AsString;
                }

                // Extract userId and gifterUserId
                string userId = doc.ContainsKey("userId") ? doc["userId"].ToString() : null;
                string gifterUserId = doc.ContainsKey("gifterUserId") ? doc["gifterUserId"].ToString() : null;
                // Skip records without required fields
                if (string.IsNullOrEmpty(oid) || string.IsNullOrEmpty(subscribedAtDate) || string.IsNullOrEmpty(userId) || string.IsNullOrEmpty(gifterUserId))
                {
                    WriteLog("WARN", $"Skipping record with missing required fields: oid={oid}, userId={userId}, gifterUserId={gifterUserId}");
                    continue;
                }

                // Transform to Laravel API format
                // Use Dictionary to handle $oid and $date property names
                var record = new Dictionary<string, object>
                {
                    ["_id"] = new Dictionary<string, object>
                    {
                        ["$oid"] = oid
                    },
                    ["subscribedAt"] = new Dictionary<string, object>
                    {
                        ["$date"] = subscribedAtDate
                    },
                    ["userId"] = userId,
                    ["gifterUserId"] = gifterUserId
                };
                subscriptionHistory.Add(record);
            }

            return subscriptionHistory;
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"ReadSubscriptionHistoryFromDb error: {ex.Message}");
            return null;
        }
    }

    /// <summary>
    /// Post subscription history to Laravel API
    /// </summary>
    private bool PostToApi(List<object> subscriptionHistory)
    {
        try
        {
            // Prepare payload
            var payload = new
            {
                subscription_history = subscriptionHistory
            };
            string json = JsonConvert.SerializeObject(payload);
            var content = new StringContent(json, Encoding.UTF8, "application/json");
            string url = $"{API_BASE_URL}/api/twitch/subscription-history";
            // Post to API
            HttpResponseMessage response = _httpClient.PostAsync(url, content).GetAwaiter().GetResult();
            if (response.IsSuccessStatusCode)
            {
                WriteLog("INFO", $"✓ Posted subscription history - {response.StatusCode}");
                return true;
            }
            else
            {
                string responseBody = response.Content.ReadAsStringAsync().GetAwaiter().GetResult();
                WriteLog("ERROR", $"✗ Failed to post subscription history - {response.StatusCode}: {response.ReasonPhrase}");
                WriteLog("ERROR", $"Response body: {responseBody}");
                return false;
            }
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"PostToApi error: {ex.Message}");
            return false;
        }
    }
}