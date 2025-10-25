#!/usr/bin/env python3
"""
Benchmark Analysis Script for Twitch Stats Posting

This script parses the benchmark logs from both C# (poster.cs) and Laravel,
analyzes the performance data, and generates comparison reports.

Usage:
    python analyze_benchmarks.py
    python analyze_benchmarks.py --csharp-log logs/streamerbot_poster.log
    python analyze_benchmarks.py --laravel-log storage/logs/laravel.log
"""

import json
import re
from datetime import datetime
from statistics import mean, stdev, median
from pathlib import Path
import argparse
from typing import List, Dict, Any
from collections import defaultdict


class BenchmarkAnalyzer:
    """Analyzes benchmark data from C# and Laravel logs"""

    def __init__(self, csharp_log_path: str, laravel_log_path: str):
        self.csharp_log_path = Path(csharp_log_path)
        self.laravel_log_path = Path(laravel_log_path)
        self.benchmarks = []
        self.run_summaries = []
        self.laravel_benchmarks = []

    def parse_csharp_logs(self) -> None:
        """Parse C# benchmark logs"""
        if not self.csharp_log_path.exists():
            print(f"⚠️  C# log file not found: {self.csharp_log_path}")
            return

        with open(self.csharp_log_path, 'r') as f:
            lines = f.readlines()

        for line in lines:
            # Parse individual request benchmarks
            if '[BENCHMARK]' in line:
                json_match = re.search(r'\[BENCHMARK\] (.+)$', line)
                if json_match:
                    try:
                        data = json.loads(json_match.group(1))
                        self.benchmarks.append(data)
                    except json.JSONDecodeError:
                        continue

            # Parse run summaries
            if '[RUN_SUMMARY]' in line:
                json_match = re.search(r'\[RUN_SUMMARY\] (.+)$', line)
                if json_match:
                    try:
                        data = json.loads(json_match.group(1))
                        self.run_summaries.append(data)
                    except json.JSONDecodeError:
                        continue

    def parse_laravel_logs(self) -> None:
        """Parse Laravel benchmark logs"""
        if not self.laravel_log_path.exists():
            print(f"⚠️  Laravel log file not found: {self.laravel_log_path}")
            return

        with open(self.laravel_log_path, 'r') as f:
            content = f.read()

        # Find all BENCHMARK log entries
        pattern = r'\[.*?\] local\.INFO: BENCHMARK: Twitch stats import (.*?)(?=\[.*?\] |$)'
        matches = re.finditer(pattern, content, re.DOTALL)

        for match in matches:
            try:
                # Extract JSON from Laravel log format
                json_str = match.group(1).strip()
                data = json.loads(json_str)
                self.laravel_benchmarks.append(data)
            except json.JSONDecodeError:
                continue

    def analyze_by_approach(self) -> Dict[str, Any]:
        """Analyze run summaries grouped by approach"""
        approaches = defaultdict(list)

        for run in self.run_summaries:
            approach = run.get('approach', 'UNKNOWN')
            approaches[approach].append(run)

        results = {}

        for approach, runs in approaches.items():
            if not runs:
                continue

            durations = [r['totalDurationMs'] for r in runs]
            network_times = [r['totalNetworkMs'] for r in runs]
            serialization_times = [r['totalSerializationMs'] for r in runs]
            payload_sizes = [r['totalPayloadKB'] for r in runs]

            results[approach] = {
                'runs': len(runs),
                'duration': {
                    'min': round(min(durations), 2),
                    'max': round(max(durations), 2),
                    'mean': round(mean(durations), 2),
                    'median': round(median(durations), 2),
                    'stdev': round(stdev(durations), 2) if len(durations) > 1 else 0,
                },
                'network': {
                    'min': round(min(network_times), 2),
                    'max': round(max(network_times), 2),
                    'mean': round(mean(network_times), 2),
                },
                'serialization': {
                    'mean': round(mean(serialization_times), 2),
                },
                'payload': {
                    'mean_kb': round(mean(payload_sizes), 2),
                },
                'total_records': runs[0].get('totalRecords', 0),
                'total_requests': runs[0].get('totalRequests', 0),
            }

        return results

    def analyze_laravel_performance(self) -> Dict[str, Any]:
        """Analyze Laravel backend performance"""
        if not self.laravel_benchmarks:
            return {}

        timings = []
        for bench in self.laravel_benchmarks:
            if 'timing' in bench:
                timings.append(bench['timing'])

        if not timings:
            return {}

        result = {
            'runs': len(timings),
            'total_ms': {
                'mean': round(mean([t['total_ms'] for t in timings]), 2),
                'min': round(min([t['total_ms'] for t in timings]), 2),
                'max': round(max([t['total_ms'] for t in timings]), 2),
            },
            'breakdown': {}
        }

        # Calculate means for each timing component
        timing_keys = ['user_upsert_ms', 'user_mapping_ms', 'stats_prepare_ms',
                       'existing_check_ms', 'stats_upsert_ms']

        for key in timing_keys:
            values = [t[key] for t in timings if key in t]
            if values:
                result['breakdown'][key] = round(mean(values), 2)

        # Memory stats
        if self.laravel_benchmarks and 'memory' in self.laravel_benchmarks[0]:
            memory_data = [b['memory'] for b in self.laravel_benchmarks]
            result['memory'] = {
                'peak_mb_mean': round(mean([m['peak_mb'] for m in memory_data]), 2),
                'used_mb_mean': round(mean([m['used_mb'] for m in memory_data]), 2),
            }

        return result

    def print_report(self) -> None:
        """Print comprehensive analysis report"""
        print("\n" + "="*80)
        print(" 📊 BENCHMARK ANALYSIS REPORT")
        print("="*80 + "\n")

        # C# Client-Side Analysis
        approach_analysis = self.analyze_by_approach()

        if approach_analysis:
            print("🔷 C# CLIENT-SIDE PERFORMANCE\n")

            for approach, stats in approach_analysis.items():
                print(f"Approach: {approach}")
                print(f"  Runs: {stats['runs']}")
                print(f"  Total Records: {stats['total_records']}")
                print(f"  Total Requests: {stats['total_requests']}")
                print(f"\n  ⏱️  End-to-End Duration:")
                print(f"    Mean:   {stats['duration']['mean']:>8.2f} ms")
                print(f"    Median: {stats['duration']['median']:>8.2f} ms")
                print(f"    Min:    {stats['duration']['min']:>8.2f} ms")
                print(f"    Max:    {stats['duration']['max']:>8.2f} ms")
                if stats['duration']['stdev'] > 0:
                    print(f"    StdDev: {stats['duration']['stdev']:>8.2f} ms")

                print(f"\n  🌐 Network Time (Total):")
                print(f"    Mean:   {stats['network']['mean']:>8.2f} ms")
                print(f"    Min:    {stats['network']['min']:>8.2f} ms")
                print(f"    Max:    {stats['network']['max']:>8.2f} ms")

                print(f"\n  📦 Serialization Time (Total):")
                print(f"    Mean:   {stats['serialization']['mean']:>8.2f} ms")

                print(f"\n  💾 Payload Size (Total):")
                print(f"    Mean:   {stats['payload']['mean_kb']:>8.2f} KB")

                print("\n" + "-"*80 + "\n")

        # Laravel Backend Analysis
        laravel_stats = self.analyze_laravel_performance()

        if laravel_stats:
            print("🔶 LARAVEL BACKEND PERFORMANCE\n")
            print(f"  Runs: {laravel_stats['runs']}")
            print(f"\n  ⏱️  Processing Time:")
            print(f"    Mean:   {laravel_stats['total_ms']['mean']:>8.2f} ms")
            print(f"    Min:    {laravel_stats['total_ms']['min']:>8.2f} ms")
            print(f"    Max:    {laravel_stats['total_ms']['max']:>8.2f} ms")

            if 'breakdown' in laravel_stats:
                print(f"\n  🔍 Time Breakdown:")
                for key, value in laravel_stats['breakdown'].items():
                    label = key.replace('_', ' ').title().replace('Ms', '')
                    print(f"    {label:<20} {value:>8.2f} ms")

            if 'memory' in laravel_stats:
                print(f"\n  💾 Memory Usage:")
                print(f"    Peak (Mean):  {laravel_stats['memory']['peak_mb_mean']:>6.2f} MB")
                print(f"    Used (Mean):  {laravel_stats['memory']['used_mb_mean']:>6.2f} MB")

            print("\n" + "-"*80 + "\n")

        # Comparison
        if len(approach_analysis) > 1:
            print("⚖️  COMPARISON\n")
            approaches = list(approach_analysis.items())

            if len(approaches) == 2:
                approach1, stats1 = approaches[0]
                approach2, stats2 = approaches[1]

                duration_diff = stats1['duration']['mean'] - stats2['duration']['mean']
                duration_pct = (duration_diff / stats1['duration']['mean']) * 100

                print(f"  {approach1} vs {approach2}:")
                print(f"    Duration Difference: {abs(duration_diff):.2f} ms ({abs(duration_pct):.1f}%)")

                if duration_diff > 0:
                    print(f"    ✅ {approach2} is FASTER by {abs(duration_diff):.2f} ms")
                else:
                    print(f"    ✅ {approach1} is FASTER by {abs(duration_diff):.2f} ms")

                network_diff = stats1['network']['mean'] - stats2['network']['mean']
                print(f"\n    Network Time Difference: {abs(network_diff):.2f} ms")

                payload_diff = stats1['payload']['mean_kb'] - stats2['payload']['mean_kb']
                print(f"    Payload Size Difference: {abs(payload_diff):.2f} KB")

            print("\n" + "-"*80 + "\n")

        # Raw data summary
        print("📋 DATA SUMMARY\n")
        print(f"  C# Benchmark Entries: {len(self.benchmarks)}")
        print(f"  C# Run Summaries: {len(self.run_summaries)}")
        print(f"  Laravel Benchmarks: {len(self.laravel_benchmarks)}")
        print("\n" + "="*80 + "\n")

    def export_json(self, output_path: str) -> None:
        """Export all benchmark data to JSON"""
        data = {
            'csharp': {
                'benchmarks': self.benchmarks,
                'run_summaries': self.run_summaries,
            },
            'laravel': {
                'benchmarks': self.laravel_benchmarks,
            },
            'analysis': {
                'by_approach': self.analyze_by_approach(),
                'laravel_performance': self.analyze_laravel_performance(),
            }
        }

        output_file = Path(output_path)
        with open(output_file, 'w') as f:
            json.dump(data, f, indent=2)

        print(f"✅ Exported full benchmark data to: {output_file}")


def main():
    parser = argparse.ArgumentParser(
        description='Analyze Twitch stats posting benchmark data'
    )
    parser.add_argument(
        '--csharp-log',
        default='storage/logs/streamerbot_poster.log',
        help='Path to C# log file (default: storage/logs/streamerbot_poster.log)'
    )
    parser.add_argument(
        '--laravel-log',
        default='storage/logs/laravel.log',
        help='Path to Laravel log file (default: storage/logs/laravel.log)'
    )
    parser.add_argument(
        '--export',
        help='Export full benchmark data to JSON file'
    )

    args = parser.parse_args()

    analyzer = BenchmarkAnalyzer(args.csharp_log, args.laravel_log)

    print("🔍 Parsing logs...")
    analyzer.parse_csharp_logs()
    analyzer.parse_laravel_logs()

    analyzer.print_report()

    if args.export:
        analyzer.export_json(args.export)


if __name__ == '__main__':
    main()

