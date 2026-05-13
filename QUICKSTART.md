# Quick Start Guide

## Local Testing (Without AWS)

For local testing with JSON files, simply mount the files and provide their absolute paths:

```bash
docker run --rm \
  -v /path/to/before.json:/before.json \
  -v /path/to/after.json:/after.json \
  moodlehq/moodle-bench-diff:latest \
  php bin/console moodle:compare-results /before.json /after.json
```

## Web Interface (Port Configuration)

**If you have Apache on port 80** (or another service on port 8000), use port 8080:

```bash
docker run --rm -p 8080:80 \
  -v /path/to/your/benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```

Access at: **http://localhost:8080**

**Other available ports:** 9000, 3000, or any free port
See [PORT_CONFIGURATION.md](PORT_CONFIGURATION.md) for details.

### With Verbose Output

To see detailed comparison results for all metrics:

```bash
docker run --rm \
  -v /path/to/before.json:/before.json \
  -v /path/to/after.json:/after.json \
  moodlehq/moodle-bench-diff:latest \
  php bin/console moodle:compare-results --verbose /before.json /after.json
```

## AWS S3 Integration

If you have data stored in AWS S3, configure AWS credentials:

```bash
docker run --rm \
  -e AWS_S3_BUCKET=your-bucket-name \
  -e AWS_REGION=us-east-1 \
  -e AWS_ACCESS_KEY=your-access-key \
  -e AWS_ACCESS_SECRET_KEY=your-secret-key \
  moodlehq/moodle-bench-diff:latest \
  php bin/console moodle:compare-results dataset1 dataset2
```

## Local Installation

If you prefer to run locally without Docker:

```bash
cd application
composer install
php bin/console moodle:compare-results /path/to/before.json /path/to/after.json
```

## Example Output

### Summary Output (Without --verbose)

```
Comparing results between /before.json and /after.json
======================================================

 --------- --------- --------- 
           Before    After     
 --------- --------- --------- 
  Commit    abc123    def456    
  Size      XS        XS        
  ...
 --------- --------- --------- 

 [INFO] Comparison successful
```

### Detailed Output (With --verbose)

Shows individual scenario comparisons with:
- Scenario name
- Metric being compared
- Before value vs After value
- Whether the change is improved, degraded, or no change

## Troubleshooting

### File Not Found

If you get "Dataset not found":
- Ensure file paths are absolute (start with `/` or `./`)
- Mount the files correctly in Docker with `-v host/path:container/path`
- Verify the file exists and is readable

### AWS Errors

If you get "S3 client is not configured":
- You're trying to load from S3 without AWS credentials
- Either provide AWS credentials or use local JSON files with absolute paths

## Files Included

- `before.json` - Baseline performance benchmark
- `after.json` - Target performance benchmark for comparison

See README.md for complete documentation.

