# Moodle Bench Diff

A Symfony-based performance benchmarking comparison tool for Moodle. This tool compares performance testing data between two runs (a "before" and "after" scenario) to identify performance regressions or improvements.

## Overview

The moodle-bench-diff tool is used in the Moodle CI pipeline to compare performance metrics between two Moodle site configurations:

- **Site A (Before)**: The baseline performance benchmark
- **Site B (After)**: The target configuration to compare against

It analyzes various metrics including:
- Database operations (reads, writes, query times)
- Memory usage
- HTTP response times and latency
- File includes
- Server load
- Session size
- Thread-specific performance data

## 🎨 Web Interface (Visual Comparison Tool)

**NEW FEATURE**: The tool includes a **fully-featured web dashboard** with interactive charts!

### Quick Access
```bash
docker run --rm -p 8000:80 \
  -v /path/to/your/benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```

Then visit: **http://localhost:8000**

### Features
✅ Interactive dashboard with dataset filtering
✅ Visual bar charts for all performance metrics
✅ Side-by-side dataset comparison
✅ Mobile-responsive design
✅ Bootstrap 5 styling
✅ Real-time chart rendering

### Screenshots/UI Components
- **Filter Section**: Filter datasets by branch, size, users, throughput, etc.
- **Dataset Selector**: Check boxes to select which benchmarks to compare
- **Summary Table**: Configuration details of selected datasets
- **Performance Charts**: Horizontal bar charts for each metric
  - Database metrics (reads, writes, query time)
  - Resource metrics (memory, files, server load)
  - Network metrics (bytes, latency, response time)

**See [WEB_INTERFACE.md](WEB_INTERFACE.md) and [WEB_USAGE_GUIDE.md](WEB_USAGE_GUIDE.md) for detailed information.**

## Features

- **Performance Comparison**: Compares metrics between two benchmark runs
- **Detailed Reporting**: Generates detailed comparison reports highlighting differences
- **Scenario-based Analysis**: Breaks down performance by individual test scenarios (Login, Frontpage, etc.)
- **Multiple Data Sources**: Supports loading data from local files or AWS S3
- **Status Detection**: Identifies performance regressions and improvements

## Project Structure

```
application/
├── bin/
│   └── console           # Symfony console entry point
├── config/
│   ├── bundles.php       # Bundle configuration
│   ├── services.yaml     # Service definitions
│   └── packages/         # Package-specific configuration
├── public/
│   └── index.php         # Web entry point
├── src/
│   ├── Command/          # Console commands
│   │   ├── CompareResultsCommand.php      # Main comparison command
│   │   └── MoodleWarmCacheCommand.php     # Cache warming command
│   ├── Controller/       # Web controllers
│   ├── Model/            # Data models
│   │   ├── Dataset.php
│   │   ├── Scenario.php
│   │   ├── Result.php
│   │   └── ComparisonResult.php
│   └── Service/          # Business logic services
│       ├── DatasetLoaderInterface.php     # Dataset loading interface
│       ├── FilebasedDatasetLoader.php     # Local file loader
│       ├── S3DatasetLoader.php            # AWS S3 loader
│       ├── DatasetComparator.php          # Comparison logic
│       └── DatasetFilter.php              # Dataset filtering
├── templates/            # Twig templates for web UI
├── tests/                # Unit tests
└── vendor/               # Composer dependencies
```

## Setup

### Requirements

- PHP 8.3+
- Docker (recommended)
- Composer
- For S3 access: AWS credentials

### Installation

1. Clone the repository
2. Navigate to the `application` directory
3. Install dependencies:

```bash
cd application
composer install
```

### Environment Configuration

The application requires several environment variables:

**For S3 access (optional, for remote data sources):**
```bash
AWS_S3_BUCKET=your-bucket-name
AWS_REGION=us-east-1
AWS_ACCESS_KEY=your-aws-access-key
AWS_ACCESS_SECRET_KEY=your-aws-secret-key
APP_SECRET=your-app-secret
```

**For local file testing** (no AWS credentials needed):
```bash
APP_SECRET=your-app-secret
```

## Usage

### Command Line Interface

#### Compare Performance Results

The main command for comparing performance data:

```bash
php bin/console moodle:compare-results [--verbose] <before> <after>
```

**Parameters:**
- `<before>`: Path to the "before" dataset (JSON file or S3 location)
- `<after>`: Path to the "after" dataset (JSON file or S3 location)

**Options:**
- `--verbose`: Show detailed results for all comparisons

**Example with local files:**
```bash
php bin/console moodle:compare-results before.json after.json
```

**Example with S3 paths:**
```bash
php bin/console moodle:compare-results some-run-name another-run-name
```

### Web Interface

Access the interactive web dashboard for visual comparison:

#### Start Web Server with Docker
```bash
docker run --rm -p 8000:80 \
  -v /path/to/your/benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```

Then visit: **http://localhost:8000**

#### Start Web Server Locally
```bash
cd application
symfony server:start
```

Visit: **http://localhost:8000**

#### Web Interface Features
- 📊 Interactive dashboard
- 📈 Visual bar charts for all metrics
- 🔍 Advanced filtering by multiple criteria
- 📱 Mobile-responsive design
- ⚡ Real-time chart rendering

See [WEB_USAGE_GUIDE.md](WEB_USAGE_GUIDE.md) for detailed usage instructions.

### Docker Usage

The tool is containerized and can be run without local installation:

**Test with local files:**
```bash
docker run --rm \
  -v /path/to/before.json:/before.json \
  -v /path/to/after.json:/after.json \
  moodlehq/moodle-bench-diff:latest \
  php bin/console moodle:compare-results /before.json /after.json
```

**With AWS S3 access:**
```bash
docker run --rm \
  -e AWS_S3_BUCKET=bucket-name \
  -e AWS_REGION=us-east-1 \
  -e AWS_ACCESS_KEY=key \
  -e AWS_ACCESS_SECRET_KEY=secret \
  moodlehq/moodle-bench-diff:latest \
  php bin/console moodle:compare-results dataset1 dataset2
```

## Data Format

### JSON Schema

Both the before and after JSON files should follow this structure:

```json
{
  "filename": "rundata.json",
  "host": "webserver-hostname",
  "group": "test-group-id",
  "rundesc": "description-or-commit-hash",
  "users": "number-of-concurrent-users",
  "loopcount": "number-of-loops",
  "rampup": "ramp-up-seconds",
  "throughput": "throughput-value",
  "size": "XS|S|M|L|XL",
  "baseversion": "moodle-version-id",
  "siteversion": "site-version",
  "sitebranch": "branch-name",
  "sitecommit": "commit-hash",
  "results": [
    [
      {
        "thread": 0,
        "starttime": 1234567890,
        "dbreads": 100,
        "dbwrites": 10,
        "dbquerytime": 0.5,
        "memoryused": "10.5",
        "filesincluded": "500",
        "serverload": "2.5",
        "sessionsize": "5.0",
        "timeused": "0.250",
        "name": "Scenario Name",
        "url": "http://example.com/path",
        "bytes": "100000",
        "time": "500",
        "latency": "100"
      }
    ]
  ]
}
```

## Key Classes and Interfaces

### DatasetLoaderInterface
Abstract interface for loading benchmark datasets.

**Implementations:**
- `FilebasedDatasetLoader`: Loads JSON files from the local filesystem
- `S3DatasetLoader`: Loads JSON files from AWS S3 buckets

### Dataset Model
Represents a complete benchmark run with all scenarios and results.

### ComparisonResult
Holds the results of comparing two datasets, including:
- Pass/fail status for each scenario metric
- Before and after values
- Detailed descriptions of differences

### DatasetComparator
Core business logic for comparing two datasets and identifying:
- Performance regressions (negative changes)
- Performance improvements (positive changes)
- Metrics within acceptable thresholds

## Testing

Run the test suite:

```bash
php bin/phpunit
```

Or within Docker:

```bash
docker run --rm moodlehq/moodle-bench-diff:latest php bin/phpunit
```

## Development

### Building the Docker Image

```bash
docker build -t moodlehq/moodle-bench-diff:latest .
```

### Local Development Server

Start the Symfony development server:

```bash
cd application
symfony server:start
```

The web interface will be available at `http://localhost:8000`

## Troubleshooting

### "Environment variable not found: AWS_S3_BUCKET"

This error occurs when trying to use S3 without AWS credentials configured. **Solutions:**

1. **For local testing**, use absolute paths to JSON files:
   ```bash
   php bin/console moodle:compare-results /path/to/before.json /path/to/after.json
   ```

2. **For Docker**, ensure you mount the files as absolute paths:
   ```bash
   docker run --rm \
     -v /path/to/before.json:/before.json \
     -v /path/to/after.json:/after.json \
     moodlehq/moodle-bench-diff:latest \
     php bin/console moodle:compare-results /before.json /after.json
   ```

3. **For S3 access**, provide AWS credentials via environment variables.

### Dataset Not Found

- Verify the file path is correct and accessible
- Ensure JSON files are valid JSON
- Check file permissions (must be readable)

## Performance Comparison Metrics

The tool evaluates the following metrics for each scenario:

1. **Database Metrics**
   - Number of database reads
   - Number of database writes
   - Total database query time

2. **Memory Metrics**
   - Memory usage (MB)
   - Session size (KB)

3. **Performance Metrics**
   - Response time (milliseconds)
   - Time used (seconds)
   - HTTP bytes transferred
   - Latency (milliseconds)

4. **System Metrics**
   - Number of files included
   - Server load average
   - Throughput

## Architecture

The application follows Symfony best practices:

- **MVC Pattern**: Controllers handle requests, Services contain business logic
- **Dependency Injection**: All services are configured via the service container
- **Interface-based Design**: Pluggable loaders and comparators allow easy extension
- **Console Commands**: CLI interface via Symfony Console component
- **Web UI**: Optional web interface for browsing and comparing datasets

## Contributing

Contributions are welcome. Please follow Symfony coding standards and include tests for new features.

## License

See LICENSE file for details.

## Support

For issues, questions, or contributions, please refer to the Moodle CI documentation or contact the Moodle HQ team.

urr
