# PROJECT.md - Moodle Bench Diff

## Project Overview

**Name:** moodle-bench-diff
**Purpose:** Performance benchmark comparison tool for Moodle
**Type:** Symfony CLI + Web Application
**Language:** PHP 8.3
**Framework:** Symfony 7.x
**Status:** Production-Ready

The tool compares performance testing data between two Moodle site configurations (baseline vs. target) and generates detailed comparison reports with visual charts and metrics analysis.

## Project Context

### What It Does
- **Compares performance benchmarks** between two Moodle installations
- **Analyzes 11+ metrics**: database operations, memory, response times, latency, server load, etc.
- **CLI Interface**: Integration with CI/CD pipelines for automated testing
- **Web Dashboard**: Interactive visual charts with Bootstrap 5 and Chart.js
- **Flexible Data Loading**: Seamlessly works with local JSON files or AWS S3
- **Works Offline**: No AWS credentials required for local development

### Key Problems It Solves
- ✅ Identifies performance regressions when code changes
- ✅ Measures performance improvements from optimizations
- ✅ Tracks performance trends over time
- ✅ Provides stakeholder-friendly visual comparisons
- ✅ Enables data-driven decision making for optimization efforts
- ✅ Documents performance impact of pull requests

### Recent Improvements
- **Fixed S3 Client Error**: Tool now gracefully handles missing AWS credentials
- **Null Check Protection**: Added defensive checks in S3DatasetLoader.listDatasets()
- **Hybrid Data Loading**: Auto-detection of local vs. S3 data sources
- **Local Testing Ready**: Run locally without Docker or AWS setup

## Architecture

### High-Level Flow

```
User Input (CLI/Web)
    ↓
CompareResultsCommand (CLI) / IndexController (Web)
    ↓
HybridDatasetLoader (Intelligent Router)
    ├─→ FilebasedDatasetLoader (Local JSON files)
    │   └─→ Filesystem + FilesystemAdapter (caching)
    └─→ S3DatasetLoader (AWS S3)
        └─→ AWS SDK + FilesystemAdapter (caching)
    ↓
Dataset Models (Data transformation)
    ├─→ Dataset (Complete benchmark run)
    ├─→ Scenario (Test scenario with multiple executions)
    └─→ Result (Individual metric value)
    ↓
DatasetComparator (Analysis engine)
    ├─→ Calculates differences
    ├─→ Identifies improvements/regressions
    └─→ Generates comparison metadata
    ↓
Output Formatters
    ├─→ CLI Table (CompareResultsCommand)
    └─→ Web Charts/Forms (IndexController + Twig templates)
```

## Technology Stack

### Backend
- **PHP 8.3** with Apache 2.4
- **Symfony 7.x** framework
- **Doctrine ORM** (if needed, currently file/S3 based)
- **PHPUnit** for testing

### Frontend
- **Bootstrap 5** CSS framework
- **Chart.js** for interactive graphs
- **Stimulus.js** for client-side interactivity
- **Twig** template engine

### Infrastructure
- **Docker** containerization (PHP 8.3 Apache)
- **AWS S3** optional storage backend
- **Filesystem caching** for performance optimization

## File Structure

### Key Directories
```
application/
├── src/
│   ├── Command/
│   │   └── CompareResultsCommand.php     # CLI entry point
│   ├── Controller/
│   │   ├── IndexController.php            # Dashboard
│   │   └── ComparisonController.php       # Comparison view
│   ├── Service/
│   │   ├── HybridDatasetLoader.php        # Smart data router (CRITICAL)
│   │   ├── FilebasedDatasetLoader.php     # Local file support
│   │   ├── S3DatasetLoader.php            # S3 support
│   │   └── DatasetComparator.php          # Comparison logic
│   ├── Model/
│   │   ├── Dataset.php                    # Data container
│   │   ├── Scenario.php                   # Test scenario
│   │   └── Result.php                     # Metric value
│   └── Form/
│       ├── DatasetFilterType.php          # Dashboard filters
│       └── DatasetComparisonType.php      # Comparison form
├── templates/
│   ├── base.html.twig                     # Base layout
│   ├── index/index.html.twig              # Dashboard
│   └── comparison/compare.html.twig       # Comparison view
├── assets/
│   ├── app.js                             # Frontend JS
│   ├── styles/app.css                     # Custom styles
│   └── controllers/hello_controller.js    # Stimulus controllers
├── config/
│   ├── services.yaml                      # Service configuration
│   └── routes.yaml                        # Route definitions
└── tests/
    └── Service/
        └── DatasetComparatorTest.php      # Unit tests
```

## Core Components Explained

### 1. HybridDatasetLoader (CRITICAL - Smart Router)
**Location:** `src/Service/HybridDatasetLoader.php`

**Purpose:** Intelligently routes data loading requests to the appropriate backend

**How it works:**
```php
if (isLocalFilePath($path)) {
    return filebasedLoader->loadFullDataset($path);
} else if (s3Loader !== null) {
    return s3Loader->loadFullDataset($path);
} else {
    return filebasedLoader->loadFullDataset($path);  // fallback
}
```

**Why it's important:**
- Enables seamless operation with/without AWS
- No configuration changes needed to switch data sources
- Provides graceful degradation if S3 is unavailable

### 2. DatasetComparator (Analysis Engine)
**Location:** `src/Service/DatasetComparator.php`

**Capabilities:**
- Compares metrics across scenarios
- Calculates percentage differences
- Identifies improvements (✓), regressions (✗), no change (=)
- Generates detailed comparison metadata

**Metrics Analyzed:**
- Database: reads, writes, query time
- Resource: memory, files included, server load
- Network: bytes, latency, response time
- Session: session size
- Execution: time used

### 3. FilebasedDatasetLoader (Local File Support)
**Location:** `src/Service/FilebasedDatasetLoader.php`

**Features:**
- Loads JSON from local filesystem
- Works with mounted volumes in Docker
- Includes caching for repeated loads
- No external dependencies

### 4. S3DatasetLoader (AWS Integration)
**Location:** `src/Service/S3DatasetLoader.php`

**Features:**
- Uses AWS SDK v3
- Lists objects from S3 bucket
- Downloads and parses JSON files
- Intelligent caching

**Note:** S3 client can be null (graceful degradation)

### 5. Dashboard (Web Interface)
**Location:** `src/Controller/IndexController.php`

**Features:**
- Lists available datasets
- Advanced filtering (branch, size, users, etc.)
- Dataset selection and comparison
- Form-based UI with Symfony Forms
- Bootstrap 5 responsive design

## Data Flow Example

### CLI Comparison
```bash
$ docker run --rm \
  -v ./benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest \
  php bin/console moodle:compare-results \
    /var/www/runs/before.json \
    /var/www/runs/after.json
```

**Flow:**
1. CLI argument parsing
2. CompareResultsCommand executes
3. HybridDatasetLoader detects `/var/www/runs/before.json` as local file
4. FilebasedDatasetLoader reads JSON
5. Loads data into Dataset objects
6. DatasetComparator analyzes differences
7. Outputs formatted table

### Web Dashboard
```
1. User visits http://localhost:8888/
2. IndexController -> loads all datasets via HybridDatasetLoader
3. Shows filtering form + dataset checklist
4. User selects datasets and submits
5. Generates charts via Chart.js
6. Real-time comparison display
```

## Performance Metrics Reference

### Database Metrics
- **dbreads**: Number of database read queries
- **dbwrites**: Number of database write queries
- **dbquerytime**: Total database query execution time (seconds)

### Resource Metrics
- **memoryused**: Peak memory consumption (MB)
- **filesincluded**: Number of PHP files included
- **serverload**: Server CPU load average

### Network Metrics
- **bytes**: HTTP response payload size
- **time**: Response time (milliseconds)
- **latency**: Network latency (milliseconds)

### Session Metrics
- **sessionsize**: Session storage size (KB)

### Execution Metrics
- **timeused**: PHP execution time (seconds)

## Common Comparison Scenarios

### Scenario 1: Code Optimization
```
Before: 100 dbreads, 5.2s execution time
After:  85 dbreads, 4.1s execution time
Result: ✓ Improved (15% reduction, 21% faster)
```

### Scenario 2: Regression Detection
```
Before: 50 dbreads
After:  75 dbreads
Result: ✗ Regression (50% increase in queries)
```

### Scenario 3: No Change
```
Before: 1000 bytes
After:  1000 bytes
Result: = No change
```

## Known Limitations & Workarounds

### Limitation 1: S3 Without Credentials
**Symptom:** `Environment variable not found: "AWS_S3_BUCKET"`
**Solution:** Now handled gracefully - will use local loader automatically

### Limitation 2: Port Conflicts
**Symptom:** `Bind for 0.0.0.0:8080 failed: port is already allocated`
**Solution:** Use different port: `-p 8888:80` instead of `-p 8080:80`

### Limitation 3: Volume Mounting
**Symptom:** Files not found in container
**Solution:** Ensure correct path: `-v ./benchmarks:/var/www/runs`

## Testing & Validation

### Unit Tests
```bash
cd application
php vendor/bin/phpunit tests/
```

### Manual CLI Test
```bash
docker run --rm \
  -v ./benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest \
  php bin/console moodle:compare-results \
    /var/www/runs/before.json \
    /var/www/runs/after.json
```

### Manual Web Test
```bash
docker run --rm -p 8888:80 \
  -v ./benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
# Visit: http://localhost:8888
```

## Development Workflow

### Local Development (Without Docker)
```bash
cd application
composer install
symfony server:start
# Visit: http://localhost:8000
```

### Adding New Metrics
1. Update Result model to include new field
2. Update comparison logic in DatasetComparator
3. Add chart configuration in controller
4. Update Twig template to display

### Docker Build
```bash
docker build -t moodlehq/moodle-bench-diff:latest .
```

## Related Files
- **README.md** - User-focused documentation
- **QUICKSTART.md** - Getting started guide
- **WEB_INTERFACE.md** - Web UI documentation
- **IMPLEMENTATION_SUMMARY.md** - Technical implementation details

### Key Components

#### Services (Business Logic)
- **HybridDatasetLoader** - Intelligently routes to local or S3 loader
- **FilebasedDatasetLoader** - Loads JSON from local filesystem
- **S3DatasetLoader** - Loads from AWS S3 buckets
- **DatasetComparator** - Compares two datasets and identifies changes
- **DatasetFilter** - Filters datasets by criteria

#### Models (Data)
- **Dataset** - Represents a complete benchmark run
- **Scenario** - Individual test scenario (Login, View Course, etc.)
- **Result** - Single measurement result from a scenario
- **ComparisonResult** - Result of comparing two datasets

#### Commands (CLI)
- **CompareResultsCommand** - Main CLI command for comparing datasets
- **MoodleWarmCacheCommand** - Cache warming command

#### Controllers (Web)
- **IndexController** - Dashboard, filtering, chart generation
- **ComparisonController** - Comparison view

## Technology Stack

### Backend
- **PHP 8.3** - Language
- **Symfony 6.4** - Web framework
- **Composer** - Dependency manager
- **Doctrine** - ORM (if needed)
- **AWS SDK** - S3 integration

### Frontend
- **Twig** - Template engine
- **Bootstrap 5** - CSS framework
- **Chart.js** - Charting library
- **Stimulus.js** - JavaScript framework
- **HTML5** - Markup

### DevOps
- **Docker** - Containerization
- **Composer** - PHP dependencies
- **NPM/Importmap** - JavaScript assets

## File Structure

```
moodle-bench-diff/
├── application/                 # Main application
│   ├── bin/
│   │   └── console             # CLI entry point
│   ├── config/
│   │   ├── services.yaml       # Service configuration
│   │   ├── bundles.php         # Bundle registration
│   │   ├── packages/
│   │   │   ├── aws.yaml        # AWS config
│   │   │   ├── framework.yaml
│   │   │   ├── cache.yaml
│   │   │   └── ...
│   │   └── routes.yaml         # URL routing
│   ├── public/
│   │   └── index.php           # Web entry point
│   ├── src/
│   │   ├── Kernel.php          # Symfony kernel
│   │   ├── Command/
│   │   │   ├── CompareResultsCommand.php
│   │   │   └── MoodleWarmCacheCommand.php
│   │   ├── Controller/
│   │   │   ├── IndexController.php
│   │   │   └── ComparisonController.php
│   │   ├── Model/
│   │   │   ├── Dataset.php
│   │   │   ├── Scenario.php
│   │   │   ├── Result.php
│   │   │   └── ComparisonResult.php
│   │   ├── Service/
│   │   │   ├── DatasetLoaderInterface.php
│   │   │   ├── FilebasedDatasetLoader.php
│   │   │   ├── S3DatasetLoader.php
│   │   │   ├── HybridDatasetLoader.php (NEW - Smart routing)
│   │   │   ├── DatasetComparator.php
│   │   │   ├── DatasetFilter.php
│   │   │   └── ...
│   │   └── Form/               # Symfony forms
│   ├── templates/              # Twig templates
│   │   ├── base.html.twig
│   │   ├── index/
│   │   │   └── index.html.twig (Dashboard)
│   │   └── comparison/
│   │       └── compare.html.twig
│   ├── assets/                 # Frontend assets
│   │   ├── app.js
│   │   ├── styles/
│   │   ├── controllers/
│   │   └── vendor/
│   ├── runs/                   # Local benchmark data (created)
│   │   ├── before.json
│   │   └── after.json
│   ├── tests/                  # Unit tests
│   │   ├── Model/
│   │   └── Service/
│   ├── vendor/                 # Composer dependencies
│   ├── var/                    # Runtime files
│   │   ├── cache/
│   │   └── log/
│   ├── composer.json
│   ├── composer.lock
│   └── phpunit.xml.dist
├── docker/
│   └── entrypoint.sh
├── Dockerfile
├── before.json                 # Test data
├── after.json                  # Test data
└── Documentation Files:
    ├── README.md               # Main documentation
    ├── QUICKSTART.md          # Quick start guide
    ├── CHANGELOG.md           # Technical changes
    ├── IMPLEMENTATION_SUMMARY.md
    ├── WEB_INTERFACE.md       # Web feature docs
    ├── WEB_USAGE_GUIDE.md     # Web usage guide
    └── PROJECT.md             # This file
```

## Key Files to Understand

### Core Logic
- **src/Service/HybridDatasetLoader.php** - Smart loader that routes to local/S3
- **src/Service/DatasetComparator.php** - Core comparison logic
- **src/Model/Dataset.php** - Main data model
- **src/Command/CompareResultsCommand.php** - CLI command implementation

### Web Interface
- **src/Controller/IndexController.php** - Main dashboard logic
- **templates/index/index.html.twig** - Dashboard UI

### Configuration
- **config/services.yaml** - Service definitions and autowiring
- **config/bundles.php** - Bundle registration (conditional AWS)

## Running the Application

### CLI Usage (Command Line)

#### Compare Local Files
```bash
# From application directory
php bin/console moodle:compare-results /path/to/before.json /path/to/after.json

# With verbose output
php bin/console moodle:compare-results --verbose /path/to/before.json /path/to/after.json
```

#### Using Docker CLI
```bash
docker run --rm \
  -v /path/to/before.json:/before.json \
  -v /path/to/after.json:/after.json \
  moodlehq/moodle-bench-diff:latest \
  php bin/console moodle:compare-results /before.json /after.json
```

### Web Interface

#### Start with Docker
```bash
docker run --rm -p 8000:80 \
  -v ./benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```

#### Start Locally
```bash
cd application
symfony server:start
```

Access at: **http://localhost:8000**

### Building Docker Image
```bash
docker build -t moodlehq/moodle-bench-diff:latest .
```

## Data Format

### Input JSON Schema
Expected structure for before.json and after.json:

```json
{
  "filename": "rundata.json",
  "host": "server-hostname",
  "group": "group-id",
  "rundesc": "description or commit hash",
  "users": "number-of-concurrent-users",
  "loopcount": "number-of-loops",
  "rampup": "ramp-up-seconds",
  "throughput": "throughput-value",
  "size": "XS|S|M|L|XL",
  "baseversion": "moodle-version",
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

## Configuration

### Environment Variables

#### For Local Testing (No AWS needed)
```bash
APP_SECRET=your-app-secret
APP_ENV=dev
```

#### For AWS S3 Access
```bash
AWS_S3_BUCKET=your-bucket-name
AWS_REGION=us-east-1
AWS_ACCESS_KEY=your-access-key
AWS_ACCESS_SECRET_KEY=your-secret-key
APP_SECRET=your-app-secret
```

### Configuration Files
- **config/services.yaml** - Service autowiring and definitions
- **config/bundles.php** - Bundle registration (conditional)
- **config/packages/aws.yaml** - AWS bundle config (optional)

## Performance Metrics Tracked

### Database
- `dbreads` - Number of database read operations
- `dbwrites` - Number of database write operations
- `dbquerytime` - Total time in database queries (seconds)

### Resource Usage
- `memoryused` - Memory consumed (MB)
- `filesincluded` - Number of PHP files loaded
- `serverload` - System load during test
- `sessionsize` - Session data size (KB)

### Network
- `bytes` (total & average) - HTTP response size
- `latency` - Request-to-response time (ms)
- `time` (total & average) - Overall response time (ms)

## Testing

### Run Unit Tests
```bash
cd application
php bin/phpunit
```

### Test With Docker
```bash
docker run --rm moodlehq/moodle-bench-diff:latest php bin/phpunit
```

### Key Test Files
- `tests/Model/ScenarioTest.php`
- `tests/Service/DatasetComparatorTest.php`

## Development Guidelines

### Adding New Metrics
1. Add field to Result model
2. Update JSON parsing in Dataset
3. Add comparison logic in DatasetComparator
4. Update templates/chart builder

### Adding New Filters
1. Update DatasetFilterType form
2. Add filter logic to DatasetFilter service
3. Update templates to show new filter

### Modifying the Web UI
1. Edit templates in `templates/`
2. Update CSS in `assets/styles/app.css`
3. Modify controller logic in `src/Controller/`

### Extending Data Sources
1. Implement DatasetLoaderInterface
2. Register in services.yaml
3. Update HybridDatasetLoader to detect and route

## Important Notes

### Recent Changes (May 2026)
- ✅ Fixed AWS credential requirement for local testing
- ✅ Added HybridDatasetLoader for smart data source routing
- ✅ Enhanced FilebasedDatasetLoader for absolute paths
- ✅ Made AWS configuration optional
- ✅ Created comprehensive documentation
- ✅ Verified web interface functionality

### Known Limitations
- AWS bundle will attempt to load even if credentials aren't set
- S3DatasetLoader requires bucket configuration when used
- Chart rendering requires modern browser with JavaScript enabled

### Best Practices
1. **Local Development**: Use local files in `runs/` directory
2. **Production**: Use AWS S3 with proper credentials
3. **Testing**: Use provided test files (before.json, after.json)
4. **Performance**: Cache dataset summaries to avoid repeated S3 calls
5. **Security**: Don't commit AWS credentials, use environment variables

## Troubleshooting

### Tool won't start
- Check PHP version (needs 8.3+)
- Verify Composer dependencies installed
- Check port isn't already in use

### No datasets showing in web UI
- Ensure `runs/` directory exists
- Check file permissions (readable)
- Verify JSON format is correct
- Check Docker volume mount is correct

### Charts not rendering
- Check browser console for errors
- Verify Chart.js is loaded
- Try different browser
- Clear browser cache

### AWS errors
- Check AWS credentials are set
- Verify S3 bucket name
- Check bucket permissions
- Verify region is correct

## Documentation Location

| Document | Purpose | Location |
|----------|---------|----------|
| README.md | Complete project documentation | Root |
| QUICKSTART.md | Quick start guide | Root |
| WEB_INTERFACE.md | Web interface features | Root |
| WEB_USAGE_GUIDE.md | Web usage instructions | Root |
| CHANGELOG.md | Technical changes | Root |
| IMPLEMENTATION_SUMMARY.md | Implementation details | Root |
| PROJECT.md | This file - Developer context | Root |

## Quick Commands Reference

```bash
# Build Docker image
docker build -t moodlehq/moodle-bench-diff:latest .

# Run CLI comparison
docker run --rm -v ./data:/var/www/runs moodlehq/moodle-bench-diff:latest \
  php bin/console moodle:compare-results /var/www/runs/before.json /var/www/runs/after.json

# Start web interface
docker run --rm -p 8000:80 -v ./data:/var/www/runs moodlehq/moodle-bench-diff:latest

# Run tests
docker run --rm moodlehq/moodle-bench-diff:latest php bin/phpunit

# Local development
cd application && symfony server:start

# Composer install
composer install

# Clear cache
symfony console cache:clear
```

## Git Workflow

### Files to Never Commit
- `vendor/` directory
- `var/cache/` and `var/log/`
- `.env.local`
- AWS credentials

### Files That Should Be Committed
- `composer.json` and `composer.lock`
- All source code in `src/`
- All templates
- Configuration files (except secrets)
- Tests
- Documentation

## Related Links

- **Moodle Project**: https://moodle.org/
- **Symfony Documentation**: https://symfony.com/doc/current/index.html
- **Docker Documentation**: https://docs.docker.com/
- **Chart.js Documentation**: https://www.chartjs.org/docs/latest/

## Contact & Support

For issues, questions, or improvements:
- Check the documentation files
- Review CHANGELOG.md for recent changes
- Check Moodle CI documentation

---

**Last Updated**: May 13, 2026
**Version**: 2.0 (with web interface)
**Status**: Production Ready

