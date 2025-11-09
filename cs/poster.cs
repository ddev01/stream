using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Net.Http;
using System.Text;
using Newtonsoft.Json;

public class CPHInline
{
    // ===== CONFIGURATION =====
    private const string API_BASE_URL = "https://gargoyled-gearldine-interfamily.ngrok-free.dev/";
    private string API_TOKEN;
    private const string LOG_FILE_PATH = "logs/streamerbot_poster.log";
    private const int TEST_LIMIT = 999; // Set to 999 to process all stats, or 1 for testing
    
    // ===== OPERATION MODE =====
    // NORMAL MODE (BENCHMARK_MODE = false):
    //   - Single request with all stats (fastest approach)
    //   - Clean, simple logging
    //   - Perfect for production use
    //
    // BENCHMARK MODE (BENCHMARK_MODE = true):
    //   - Runs multiple times for performance testing
    //   - Detailed performance metrics and JSON logging
    //   - Use for testing and optimization
    private const bool BENCHMARK_MODE = false; // Set to true to enable benchmarking mode
    private const int BENCHMARK_RUNS = 10; // Number of runs when benchmarking (only used if BENCHMARK_MODE = true)
    
    // List of stat variables to sync
    private static readonly List<string> STAT_NAMES = new List<string>
    {
        "hotPotatoesCaught",
        "hotPotatoesPassed",
        "points",
        "watchtime",
        "lurkCount",
        "lurkTime",
        "topThreeCount"
    };
    
    // ===== BENCHMARK METRICS CLASSES =====
    private class RequestMetric
    {
        public string statName { get; set; }
        public int recordCount { get; set; }
        public long payloadBytes { get; set; }
        public double payloadSizeKB { get; set; }
        public double serializationMs { get; set; }
        public double networkMs { get; set; }
        public double totalMs { get; set; }
        public int statusCode { get; set; }
        public bool success { get; set; }
        public string timestamp { get; set; }
    }
    
    private class RunSummary
    {
        public int runNumber { get; set; }
        public string approach { get; set; }
        public double totalDurationMs { get; set; }
        public int totalRequests { get; set; }
        public int totalRecords { get; set; }
        public long totalPayloadBytes { get; set; }
        public double totalPayloadKB { get; set; }
        public double totalSerializationMs { get; set; }
        public double totalNetworkMs { get; set; }
        public bool success { get; set; }
        public string timestamp { get; set; }
    }

    // ===== HTTP CLIENT =====
    private static readonly HttpClient _httpClient = new HttpClient
    {
        Timeout = TimeSpan.FromSeconds(30),
    };

    /// <summary>
    /// Custom log function that writes to a specific log file
    /// </summary>
    /// Writes log messages to a dedicated auto-purge log file
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
        _httpClient.DefaultRequestHeaders.Add("User-Agent", "StreamerBot-Poster/1.0");
    }

    public void Dispose()
    {
        // Free up allocations
        _httpClient.Dispose();
    }

    public bool Execute()
    {
        try
        {
            if (BENCHMARK_MODE)
            {
                return ExecuteBenchmark();
            }
            else
            {
                return ExecuteNormal();
            }
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"Execute error: {ex.Message} | Inner: {ex.InnerException?.Message ?? "None"} | StackTrace: {ex.StackTrace}");
            return false;
        }
    }

    /// <summary>
    /// Normal execution - single request with all stats
    /// </summary>
    private bool ExecuteNormal()
    {
        try
        {
            WriteLog("INFO", "Starting normal stats sync - SINGLE REQUEST");
            return PostAllStatsSingleRequest();
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"ExecuteNormal error: {ex.Message}");
            return false;
        }
    }

    /// <summary>
    /// Benchmark execution - multiple runs for performance testing
    /// </summary>
    private bool ExecuteBenchmark()
    {
        try
        {
            bool overallSuccess = true;
            
            WriteLog("INFO", $"========== BENCHMARK MODE - {BENCHMARK_RUNS} RUNS ==========");
            
            for (int run = 1; run <= BENCHMARK_RUNS; run++)
            {
                WriteLog("INFO", $"========== BENCHMARK RUN {run}/{BENCHMARK_RUNS} ==========");
                
                bool runSuccess = PostAllStatsSingleRequest(run);
                
                if (!runSuccess)
                {
                    overallSuccess = false;
                }
                
                // Wait between runs (except for the last one)
                if (run < BENCHMARK_RUNS)
                {
                    WriteLog("INFO", "Waiting 2 seconds before next run...");
                    System.Threading.Thread.Sleep(2000);
                }
            }
            
            WriteLog("INFO", $"========== BENCHMARK COMPLETED - {BENCHMARK_RUNS} RUNS ==========");
            return overallSuccess;
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"ExecuteBenchmark error: {ex.Message}");
            return false;
        }
    }

    /// <summary>
    /// Posts all user stats using CPH API (multiple requests approach)
    /// </summary>
    private bool PostAllUserStats(int runNumber = 1)
    {
        var runStart = DateTime.Now;
        var requestMetrics = new List<RequestMetric>();
        
        try
        {
            string mode = TEST_LIMIT >= STAT_NAMES.Count ? "FULL" : "TEST";
            WriteLog("INFO", $"Starting stats sync - MULTIPLE REQUESTS ({mode} MODE: {TEST_LIMIT}/{STAT_NAMES.Count} stats)");

            int processedCount = 0;
            
            foreach (var statName in STAT_NAMES)
            {
                if (processedCount >= TEST_LIMIT)
                {
                    WriteLog("INFO", $"Limit reached ({TEST_LIMIT}), stopping");
                    break;
                }

                var metric = ProcessStat(statName);
                if (metric == null)
                {
                    return false;
                }
                
                requestMetrics.Add(metric);
                processedCount++;
            }
            
            var runDuration = (DateTime.Now - runStart).TotalMilliseconds;
            
            // Log run summary
            var summary = new RunSummary
            {
                runNumber = runNumber,
                approach = "MULTIPLE_REQUESTS",
                totalDurationMs = Math.Round(runDuration, 2),
                totalRequests = requestMetrics.Count,
                totalRecords = 0,
                totalPayloadBytes = 0,
                totalPayloadKB = 0,
                totalSerializationMs = 0,
                totalNetworkMs = 0,
                success = true,
                timestamp = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss.fff")
            };
            
            foreach (var metric in requestMetrics)
            {
                summary.totalRecords += metric.recordCount;
                summary.totalPayloadBytes += metric.payloadBytes;
                summary.totalSerializationMs += metric.serializationMs;
                summary.totalNetworkMs += metric.networkMs;
            }
            
            summary.totalPayloadKB = Math.Round(summary.totalPayloadBytes / 1024.0, 2);
            
            string summaryJson = JsonConvert.SerializeObject(summary);
            WriteLog("RUN_SUMMARY", summaryJson);

            WriteLog("INFO", $"✓ Stats sync completed! Processed {processedCount}/{STAT_NAMES.Count} stat(s) in {Math.Round(runDuration, 2)}ms");
            return true;
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"PostAllUserStats error: {ex.Message} | Inner: {ex.InnerException?.Message ?? "None"}");
            return false;
        }
    }

    /// <summary>
    /// Process and post a single stat type
    /// </summary>
    private RequestMetric ProcessStat(string statName)
    {
        try
        {
            WriteLog("INFO", $"Processing: {statName}");
            
            // Get all users with this variable from CPH
            var userVarList = CPH.GetTwitchUsersVar<object>(statName, true);
            
            if (userVarList == null || userVarList.Count == 0)
            {
                WriteLog("WARN", $"No data for: {statName}");
                // Return empty metric for skipped stat
                return new RequestMetric
                {
                    statName = statName,
                    recordCount = 0,
                    payloadBytes = 0,
                    payloadSizeKB = 0,
                    serializationMs = 0,
                    networkMs = 0,
                    totalMs = 0,
                    statusCode = 200,
                    success = true,
                    timestamp = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss.fff")
                };
            }

            WriteLog("INFO", $"Found {userVarList.Count} users with {statName}");

            // Transform to Laravel API format
            var statsPayload = new List<object>();
            foreach (var userVar in userVarList)
            {
                statsPayload.Add(new
                {
                    userId = userVar.UserId,
                    userName = userVar.UserName,
                    platform = userVar.UserType,
                    name = statName,
                    value = userVar.Value,
                    lastWrite = userVar.LastWrite
                });
            }

            // Post to Laravel API
            return PostToApi(statsPayload, statName);
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"ProcessStat({statName}) error: {ex.Message}");
            return null;
        }
    }

    /// <summary>
    /// Post stats payload to Laravel API with detailed performance metrics
    /// </summary>
    private RequestMetric PostToApi(List<object> statsPayload, string statName)
    {
        try
        {
            var overallStart = DateTime.Now;
            
            // Measure serialization time
            var serializationStart = DateTime.Now;
            var payload = new { stats = statsPayload };
            string json = JsonConvert.SerializeObject(payload);
            var serializationDuration = (DateTime.Now - serializationStart).TotalMilliseconds;
            
            var content = new StringContent(json, Encoding.UTF8, "application/json");
            long payloadSize = Encoding.UTF8.GetByteCount(json);

            string url = $"{API_BASE_URL}/api/twitch/stats";
            
            // Measure network time
            var networkStart = DateTime.Now;
            HttpResponseMessage response = _httpClient.PostAsync(url, content).GetAwaiter().GetResult();
            var networkDuration = (DateTime.Now - networkStart).TotalMilliseconds;
            
            var totalDuration = (DateTime.Now - overallStart).TotalMilliseconds;

            // Create metric object
            var metric = new RequestMetric
            {
                statName = statName,
                recordCount = statsPayload.Count,
                payloadBytes = payloadSize,
                payloadSizeKB = Math.Round(payloadSize / 1024.0, 2),
                serializationMs = Math.Round(serializationDuration, 2),
                networkMs = Math.Round(networkDuration, 2),
                totalMs = Math.Round(totalDuration, 2),
                statusCode = (int)response.StatusCode,
                success = response.IsSuccessStatusCode,
                timestamp = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss.fff")
            };

            // Log as structured JSON for easy parsing
            string metricJson = JsonConvert.SerializeObject(metric);
            WriteLog("BENCHMARK", metricJson);

            if (response.IsSuccessStatusCode)
            {
                WriteLog("INFO", $"✓ Posted {statName} - {response.StatusCode} ({Math.Round(totalDuration, 0)}ms)");
            }
            else
            {
                WriteLog("ERROR", $"✗ Failed {statName} - {response.StatusCode}: {response.ReasonPhrase}");
            }
            
            return metric;
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"PostToApi({statName}) error: {ex.Message}");
            return null;
        }
    }
    
    /// <summary>
    /// Posts ALL stats for ALL users in a single request (normal mode)
    /// </summary>
    private bool PostAllStatsSingleRequest(int runNumber = 0)
    {
        var runStart = DateTime.Now;
        
        try
        {
            string mode = runNumber > 0 ? $"BENCHMARK RUN {runNumber}" : "NORMAL";
            WriteLog("INFO", $"Starting stats sync - SINGLE REQUEST ({mode})");

            var allStatsPayload = new List<object>();

            // Collect ALL stats for ALL users
            foreach (var statName in STAT_NAMES)
            {
                var userVarList = CPH.GetTwitchUsersVar<object>(statName, true);
                
                if (userVarList == null || userVarList.Count == 0)
                {
                    WriteLog("WARN", $"No data for: {statName}");
                    continue;
                }

                WriteLog("INFO", $"Collected {userVarList.Count} users for {statName}");

                foreach (var userVar in userVarList)
                {
                    allStatsPayload.Add(new
                    {
                        userId = userVar.UserId,
                        userName = userVar.UserName,
                        platform = userVar.UserType,
                        name = statName,
                        value = userVar.Value,
                        lastWrite = userVar.LastWrite
                    });
                }
            }

            WriteLog("INFO", $"Total records to send: {allStatsPayload.Count}");

            // Measure serialization time
            var serializationStart = DateTime.Now;
            var payload = new { stats = allStatsPayload };
            string json = JsonConvert.SerializeObject(payload);
            var serializationDuration = (DateTime.Now - serializationStart).TotalMilliseconds;
            
            var content = new StringContent(json, Encoding.UTF8, "application/json");
            long payloadSize = Encoding.UTF8.GetByteCount(json);
						WriteLog("INFO", $"POSTED PAYLOAD: {json}");
            string url = $"{API_BASE_URL}/api/twitch/stats";
            
            // Measure network time
            var networkStart = DateTime.Now;
            HttpResponseMessage response = _httpClient.PostAsync(url, content).GetAwaiter().GetResult();
            var networkDuration = (DateTime.Now - networkStart).TotalMilliseconds;
            
            var totalDuration = (DateTime.Now - runStart).TotalMilliseconds;

            // Log detailed metrics
            var metric = new RequestMetric
            {
                statName = "ALL_STATS",
                recordCount = allStatsPayload.Count,
                payloadBytes = payloadSize,
                payloadSizeKB = Math.Round(payloadSize / 1024.0, 2),
                serializationMs = Math.Round(serializationDuration, 2),
                networkMs = Math.Round(networkDuration, 2),
                totalMs = Math.Round(totalDuration, 2),
                statusCode = (int)response.StatusCode,
                success = response.IsSuccessStatusCode,
                timestamp = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss.fff")
            };
            
            string metricJson = JsonConvert.SerializeObject(metric);
            WriteLog("BENCHMARK", metricJson);
            
            // Log run summary (only in benchmark mode)
            if (runNumber > 0)
            {
                var summary = new RunSummary
                {
                    runNumber = runNumber,
                    approach = "SINGLE_REQUEST",
                    totalDurationMs = Math.Round(totalDuration, 2),
                    totalRequests = 1,
                    totalRecords = allStatsPayload.Count,
                    totalPayloadBytes = payloadSize,
                    totalPayloadKB = Math.Round(payloadSize / 1024.0, 2),
                    totalSerializationMs = Math.Round(serializationDuration, 2),
                    totalNetworkMs = Math.Round(networkDuration, 2),
                    success = response.IsSuccessStatusCode,
                    timestamp = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss.fff")
                };
                
                string summaryJson = JsonConvert.SerializeObject(summary);
                WriteLog("RUN_SUMMARY", summaryJson);
            }

            if (response.IsSuccessStatusCode)
            {
                string successMsg = runNumber > 0 
                    ? $"✓ Posted ALL stats in single request - {response.StatusCode} ({Math.Round(totalDuration, 0)}ms)"
                    : $"✓ Stats sync completed - {response.StatusCode} ({Math.Round(totalDuration, 0)}ms)";
                WriteLog("INFO", successMsg);
                return true;
            }
            else
            {
                WriteLog("ERROR", $"✗ Failed single request - {response.StatusCode}: {response.ReasonPhrase}");
                return false;
            }
        }
        catch (Exception ex)
        {
            WriteLog("ERROR", $"PostAllStatsSingleRequest error: {ex.Message} | Inner: {ex.InnerException?.Message ?? "None"}");
            return false;
        }
    }
}