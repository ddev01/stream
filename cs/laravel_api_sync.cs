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
    private const string LOG_FILE_PATH = "logs/streamerbot_api_sync.log";
    private const string DB_PATH = "data/twitch_data.db";
    private static readonly List<string> STAT_NAMES = new List<string>
    {
        "hotPotatoesCaught",
        "hotPotatoesPassed",
        "points",
        "watchtime",
        "lurkCount",
        "lurkTime",
        "topThreeCount",
		"triviaWins",
    };
    // ===== HTTP CLIENT =====
    private static readonly HttpClient _httpClient = new HttpClient
    {
        Timeout = TimeSpan.FromSeconds(30),
    };
    // ===== LIFECYCLE METHODS =====
    public void Init()
    {
        _httpClient.DefaultRequestHeaders.Clear();
        API_TOKEN = CPH.GetGlobalVar<string>("laravelApiKey", true);
        _httpClient.DefaultRequestHeaders.Add("Authorization", $"Bearer {API_TOKEN}");
        _httpClient.DefaultRequestHeaders.Add("Accept", "application/json");
        _httpClient.DefaultRequestHeaders.Add("User-Agent", "StreamerBot-API-Sync/1.0");
    }

    public void Dispose()
    {
        _httpClient?.Dispose();
    }

    public bool Execute()
    {
        return true;
    }

    // ===== PUBLIC API METHODS =====
    /// <summary>
    /// Post all Twitch stats to Laravel API (bulk sync)
    /// </summary>
    public bool PostTwitchStatsBulk()
    {
        try
        {
            WriteLog("INFO", "Starting bulk stats sync");
            var statsPayload = CollectAllTwitchStats();
            if (statsPayload.Count == 0)
            {
                WriteLog("WARN", "No stats found to sync");
                return true;
            }

            var payload = new
            {
                stats = statsPayload
            };
            bool success = PostToApi("/api/twitch/stats", payload, "bulk stats sync");
            if (success)
            {
                WriteLog("INFO", $"✓ Bulk stats sync completed - {statsPayload.Count} records");
            }

            return success;
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"PostTwitchStatsBulk error: {ex.Message} | Inner: {ex.InnerException?.Message ?? "None"} | StackTrace: {ex.StackTrace}");
            return false;
        }
    }

    /// <summary>
    /// Post single Twitch stat update to Laravel API (real-time sync)
    /// </summary>
    public bool PostTwitchStatSingle()
    {
        try
        {
            WriteLog("INFO", "Starting single stat sync");
            // Early exit validation
            var validationResult = ValidateTriggerArgs();
            if (validationResult != ValidationResult.Valid)
            {
                return validationResult == ValidationResult.Skip; // Skip = success, Invalid = failure
            }

            // Extract and build stat payload
            var(stat, statName, statValue, statUserId) = BuildStatPayloadFromArgs();
            if (stat == null)
            {
                return false; // Error already logged
            }

            var payload = new
            {
                stats = new List<object>
                {
                    stat
                }
            };
            bool success = PostToApi("/api/twitch/stats", payload, "single stat sync");
            if (success)
            {
                WriteLog("INFO", $"✓ Single stat sync completed - {statName} = {statValue} for user {statUserId}");
            }
            else
            {
                CPH.SendMessage($"Error syncing stat {statName} for user {statUserId}");
            }

            return success;
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"PostTwitchStatSingle error: {ex.Message} | Inner: {ex.InnerException?.Message ?? "None"} | StackTrace: {ex.StackTrace}");
            CPH.SendMessage($"Error syncing stat: {ex.Message}");
            return false;
        }
    }

    /// <summary>
    /// Post recent subscription history (last 5 minutes) to Laravel API
    /// </summary>
    public bool PostSubscriptionHistoryRecent()
    {
        try
        {
            WriteLog("INFO", "Starting recent subscription history sync");
            if (!File.Exists(DB_PATH))
            {
                WriteLog("ERROR", $"Database file not found at {DB_PATH}");
                CPH.SendMessage("Error: Database file not found");
                return false;
            }

            DateTime fiveMinutesAgo = DateTime.UtcNow.AddMinutes(-5);
            var subscriptionHistory = QuerySubscriptionHistory(fiveMinutesAgo);
            if (subscriptionHistory == null || subscriptionHistory.Count == 0)
            {
                WriteLog("ERROR", "No subscription history found in the last 5 minutes");
                CPH.SendMessage("No subscription history record found in the last 5 minutes");
                return false;
            }

            WriteLog("INFO", $"Found {subscriptionHistory.Count} subscription history record(s)");
            var payload = new
            {
                subscription_history = subscriptionHistory
            };
            bool success = PostToApi("/api/twitch/subscription-history", payload, "recent subscription history sync");
            if (success)
            {
                WriteLog("INFO", $"✓ Recent subscription history sync completed - {subscriptionHistory.Count} record(s)");
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

    /// <summary>
    /// Post all subscription history to Laravel API
    /// </summary>
    public bool PostSubscriptionHistoryBulk()
    {
        try
        {
            WriteLog("INFO", "Starting bulk subscription history sync");
            if (!File.Exists(DB_PATH))
            {
                WriteLog("ERROR", $"Database file not found at {DB_PATH}");
                return false;
            }

            var subscriptionHistory = QuerySubscriptionHistory(null);
            if (subscriptionHistory == null || subscriptionHistory.Count == 0)
            {
                WriteLog("WARN", "No subscription history records found in database");
                return true;
            }

            WriteLog("INFO", $"Found {subscriptionHistory.Count} subscription history records");
            var payload = new
            {
                subscription_history = subscriptionHistory
            };
            bool success = PostToApi("/api/twitch/subscription-history", payload, "subscription history sync");
            if (success)
            {
                WriteLog("INFO", $"✓ Subscription history sync completed - {subscriptionHistory.Count} records");
            }

            return success;
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"PostSubscriptionHistoryBulk error: {ex.Message} | Inner: {ex.InnerException?.Message ?? "None"} | StackTrace: {ex.StackTrace}");
            return false;
        }
    }

    // ===== VALIDATION HELPERS =====
    private enum ValidationResult
    {
        Valid,
        Skip,
        Invalid
    }

    /// <summary>
    /// Validate trigger arguments for single stat sync (early exit checks)
    /// </summary>
    private ValidationResult ValidateTriggerArgs()
    {
        // Check persisted first (only sync persisted variables)
        if (!CPH.TryGetArg("global.persisted", out bool persisted))
        {
            WriteLog("ERROR", "Missing required variable: global.persisted");
            CPH.SendMessage("Error: Missing required StreamerBot variable 'global.persisted' for stat sync");
            return ValidationResult.Invalid;
        }

        if (!persisted)
        {
            WriteLog("INFO", "Skipping non-persisted variable");
            return ValidationResult.Skip;
        }

        // Check platform second (only sync Twitch)
        if (!CPH.TryGetArg("global.userType", out string userType))
        {
            WriteLog("ERROR", "Missing required variable: global.userType");
            CPH.SendMessage("Error: Missing required StreamerBot variable 'global.userType' for stat sync");
            return ValidationResult.Invalid;
        }

        if (string.IsNullOrEmpty(userType) || !userType.Equals("twitch", StringComparison.OrdinalIgnoreCase))
        {
            WriteLog("INFO", $"Skipping non-Twitch platform: {userType}");
            return ValidationResult.Skip;
        }

        return ValidationResult.Valid;
    }

    /// <summary>
    /// Build stat payload from StreamerBot trigger arguments
    /// Returns tuple: (payload object, statName, statValue, statUserId) or (null, null, 0, null) on error
    /// </summary>
    private (object payload, string statName, int statValue, string statUserId) BuildStatPayloadFromArgs()
    {
        // Extract required variables
        if (!CPH.TryGetArg("global.userId", out string userId))
        {
            WriteLog("ERROR", "Missing required variable: global.userId");
            CPH.SendMessage("Error: Missing required StreamerBot variable 'global.userId' for stat sync");
            return (null, null, 0, null);
        }

        if (!CPH.TryGetArg("global.name", out string statName))
        {
            WriteLog("ERROR", "Missing required variable: global.name");
            CPH.SendMessage("Error: Missing required StreamerBot variable 'global.name' for stat sync");
            return (null, null, 0, null);
        }

        if (!CPH.TryGetArg("global.newValue", out string newValueStr))
        {
            WriteLog("ERROR", "Missing required variable: global.newValue");
            CPH.SendMessage("Error: Missing required StreamerBot variable 'global.newValue' for stat sync");
            return (null, null, 0, null);
        }

        // Validate stat name (only sync tracked stats)
        if (!STAT_NAMES.Contains(statName))
        {
            WriteLog("WARN", $"Skipping untracked stat: {statName} for user {userId}");
            return (null, null, 0, null);
        }

        // Get optional variables
        CPH.TryGetArg("global.userName", out string userName);
        CPH.TryGetArg("global.lastWrite", out DateTime lastWrite);
        CPH.TryGetArg("global.userType", out string userType);
        // Validate userId
        if (string.IsNullOrEmpty(userId))
        {
            WriteLog("ERROR", "Empty userId");
            CPH.SendMessage("Error: Empty userId for stat sync");
            return (null, null, 0, null);
        }

        // Convert value to int
        if (!int.TryParse(newValueStr, out int value))
        {
            WriteLog("WARN", $"Failed to convert value to int: {newValueStr} for stat {statName}, user {userId}");
            CPH.SendMessage($"Error: Invalid stat value '{newValueStr}' for {statName}");
            return (null, null, 0, null);
        }

        // Use current time if lastWrite is not provided
        if (lastWrite == default(DateTime))
        {
            lastWrite = DateTime.UtcNow;
        }

        var payload = new
        {
            userId = userId,
            userName = userName ?? string.Empty,
            platform = userType ?? "twitch",
            name = statName,
            value = value,
            lastWrite = lastWrite
        };
        return (payload, statName, value, userId);
    }

    // ===== STATS COLLECTION =====
    /// <summary>
    /// Collect all Twitch stats from CPH
    /// </summary>
    private List<object> CollectAllTwitchStats()
    {
        var allStats = new List<object>();
        foreach (var statName in STAT_NAMES)
        {
            var userVarList = CPH.GetTwitchUsersVar<object>(statName, true);
            if (userVarList == null || userVarList.Count == 0)
            {
                continue;
            }

            foreach (var userVar in userVarList)
            {
                allStats.Add(new { userId = userVar.UserId, userName = userVar.UserName, platform = userVar.UserType, name = statName, value = userVar.Value, lastWrite = userVar.LastWrite });
            }
        }

        return allStats;
    }

    // ===== API POSTING =====
    /// <summary>
    /// Generic method to post data to Laravel API
    /// </summary>
    private bool PostToApi(string endpoint, object payload, string operationName)
    {
        try
        {
            string json = JsonConvert.SerializeObject(payload);
            var content = new StringContent(json, Encoding.UTF8, "application/json");
            string url = $"{API_BASE_URL}{endpoint}";
            HttpResponseMessage response = _httpClient.PostAsync(url, content).GetAwaiter().GetResult();
            if (response.IsSuccessStatusCode)
            {
                WriteLog("INFO", $"✓ Posted {operationName} - {response.StatusCode}");
                return true;
            }
            else
            {
                string responseBody = response.Content.ReadAsStringAsync().GetAwaiter().GetResult();
                WriteLog("ERROR", $"✗ Failed to post {operationName} - {response.StatusCode}: {response.ReasonPhrase}");
                WriteLog("ERROR", $"Response body: {responseBody}");
                return false;
            }
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"PostToApi({operationName}) error: {ex.Message}");
            return false;
        }
    }

    // ===== LITEDB HELPERS =====
    /// <summary>
    /// Query subscription history from LiteDB
    /// </summary>
    /// <param name = "since">If provided, only return records since this time. If null, return all records.</param>
    private List<object> QuerySubscriptionHistory(DateTime? since)
    {
        try
        {
            var history = new List<object>();
            using (var db = new LiteDatabase($"Filename={DB_PATH};ReadOnly=true"))
            {
                var collection = db.GetCollection("subscriptionHistory");
                var results = since.HasValue ? collection.Find(Query.GTE("subscribedAt", since.Value)).ToList() : collection.FindAll().ToList();
                foreach (var doc in results)
                {
                    var record = TransformSubscriptionRecord(doc);
                    if (record != null)
                    {
                        history.Add(record);
                    }
                }
            }

            return history;
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"QuerySubscriptionHistory error: {ex.Message}");
            return null;
        }
    }

    /// <summary>
    /// Transform a subscription history BsonDocument to API format
    /// </summary>
    private Dictionary<string, object> TransformSubscriptionRecord(BsonDocument doc)
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
        // Skip records with missing required fields
        if (string.IsNullOrEmpty(oid) || string.IsNullOrEmpty(subscribedAtDate) || string.IsNullOrEmpty(userId) || string.IsNullOrEmpty(gifterUserId))
        {
            return null;
        }

        // Transform to Laravel API format
        return new Dictionary<string, object>
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
    }

    // ===== LOGGING =====
    /// <summary>
    /// Custom log function that writes to a specific log file
    /// </summary>
    private void WriteLog(string level, string message)
    {
        try
        {
            string timestamp = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss");
            string logEntry = $"[{timestamp}] [{level}] {message}";
            string logDirectory = Path.GetDirectoryName(LOG_FILE_PATH);
            if (!string.IsNullOrEmpty(logDirectory) && !Directory.Exists(logDirectory))
            {
                Directory.CreateDirectory(logDirectory);
            }

            File.AppendAllText(LOG_FILE_PATH, logEntry + Environment.NewLine);
        }
        catch (Exception ex)
        {
            CPH.LogError($"Failed to write to log: {ex.Message}");
        }
    }
}