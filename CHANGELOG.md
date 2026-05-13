# Changelog

## Changes Made to Fix Local File Testing

### Problem
The application was unable to test with local JSON files without AWS credentials. It was hardcoded to use the S3DatasetLoader, which required the AWS_S3_BUCKET environment variable to be set, causing the error:

```
Environment variable not found: "AWS_S3_BUCKET".
```

### Solution
Implemented a hybrid loader system that intelligently chooses between local file loading and S3 loading based on the input path.

### Files Modified

#### 1. `application/config/bundles.php`
- **Change**: Made AwsBundle conditional
- **Reason**: AWS bundle was being loaded even when AWS credentials weren't available
- **Details**: Bundle is now only registered if `AWS_REGION` environment variable is set

#### 2. `application/config/services.yaml`
- **Changes**:
  - Made AWS credentials optional with default empty values
  - Changed default DatasetLoaderInterface from S3DatasetLoader to HybridDatasetLoader
  - Moved AWS configuration to conditional section `when@aws_configured`
  - Added explicit S3DatasetLoader configuration with null S3Client as default
- **Reason**: Allows the application to run without AWS configuration for local testing

#### 3. `application/config/packages/aws.yaml`
- **Change**: Commented out AWS configuration
- **Reason**: AWS configuration is now handled conditionally in services.yaml

#### 4. `application/src/Kernel.php`
- **Change**: Removed AwsBundle registration override
- **Reason**: Bundle registration is now handled via bundles.php conditionally

#### 5. `application/src/Service/FilebasedDatasetLoader.php`
- **Changes**:
  - Added `isAbsolutePath()` method to detect absolute file paths
  - Updated `getDatasetPath()` to use absolute paths directly when provided
  - Added automatic `runTime` property from file modification time if not present in JSON
- **Reason**: Enables loading JSON files from any location via absolute paths, not just the configured directory

#### 6. `application/src/Service/S3DatasetLoader.php`
- **Changes**:
  - Made `S3Client` dependency optional (nullable)
  - Added null checks in `datasetExists()` and `loadFullDataset()` methods
  - Throws descriptive error when S3 is not configured
- **Reason**: Gracefully handles cases where S3 client is not initialized

### New Files Created

#### 1. `application/src/Service/HybridDatasetLoader.php`
- **Purpose**: Intelligent loader that chooses between FilebasedDatasetLoader and S3DatasetLoader
- **Logic**:
  - If path starts with `/` or `./`, use FilebasedDatasetLoader (local files)
  - If S3Loader is not available, fallback to FilebasedDatasetLoader
  - Otherwise, use S3DatasetLoader for S3 bucket access

#### 2. `README.md`
- Complete project documentation including:
  - Overview and features
  - Setup instructions
  - Usage examples
  - Architecture overview
  - Data format specification

#### 3. `QUICKSTART.md`
- Quick reference guide for common use cases
- Examples for local testing and AWS S3 integration
- Troubleshooting section

### Testing

The fix was validated with the provided test files:

```bash
docker run --rm \
  -v /path/to/before.json:/before.json \
  -v /path/to/after.json:/after.json \
  moodlehq/moodle-bench-diff:latest \
  php bin/console moodle:compare-results /before.json /after.json
```

This now successfully:
- Loads local JSON files without AWS credentials
- Compares performance metrics between runs
- Provides both summary and verbose output
- Works seamlessly with absolute file paths

### Backwards Compatibility

All changes are backwards compatible:
- S3 loading still works when AWS credentials are provided
- Existing workflows using S3 are unaffected
- The system intelligently detects and handles both local and S3 datasets

### Environment Variables

The application now handles these scenarios:

**Local Testing (No AWS needed):**
- Only `APP_SECRET` required

**S3 Access (All credentials needed):**
- `AWS_S3_BUCKET`
- `AWS_REGION`
- `AWS_ACCESS_KEY`
- `AWS_ACCESS_SECRET_KEY`
- `APP_SECRET`

When AWS credentials are not provided, the application gracefully falls back to local file loading.

