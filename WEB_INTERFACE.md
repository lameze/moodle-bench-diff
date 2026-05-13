# Web Interface - Visual Comparison Tool

## ✅ YES - There IS a Web Graphic Tool!

The moodle-bench-diff project includes a **fully-featured web interface** with interactive charts and visual comparison tools. This allows you to visualize performance benchmark data graphically instead of just using the CLI.

## Accessing the Web Interface

### Starting the Web Server

**Option 1: Using Docker (Recommended)**
```bash
docker run -p 8000:80 \
  moodlehq/moodle-bench-diff:latest
```

Then access the tool at: **http://localhost:8000**

**Option 2: Local Development Server**
```bash
cd application
symfony server:start
```

Access at: **http://localhost:8000** (or the port shown)

**Option 3: Using Docker with Data Volume**
```bash
docker run -p 8000:80 \
  -v /path/to/runs:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```

Then visit: **http://localhost:8000**

## Web Interface Features

### 1. Dashboard / Dataset Browser
**URL:** `/` (home page)

**Features:**
- List all available datasets from your data source
- Filter datasets by:
  - Site branch
  - Server size (XS, S, M, L, XL)
  - Number of users
  - Loop count
  - Throughput settings
  - Ramp-up time
  - Base version
  - Site path
  - Run description

- Select multiple datasets to compare
- View detailed run information in table format

### 2. Visual Charts and Graphs
**Features:**
- Interactive bar charts comparing metrics across selected datasets
- Charts display:
  - Database reads/writes
  - Query times
  - Memory usage
  - Response times
  - File includes
  - Server load
  - Session size
  - Bytes transferred
  - Latency measurements

- Horizontal bar charts for easy metric comparison
- Automatic scaling based on data range
- Charts render using **Chart.js** via Symfony UX Chartjs

### 3. Comparison Results Table
**Features:**
- Summary table showing:
  - Moodle branch
  - Timestamp of run
  - Run description
  - Test configuration (users, version, etc.)

### 4. Dataset Filtering and Selection
**Features:**
- Advanced filtering form with multiple criteria
- Multi-select form for choosing datasets to compare
- Real-time filtering of available datasets

## Technology Stack

The web interface uses:
- **Symfony Framework** (web framework)
- **Twig** (template engine)
- **Bootstrap 5** (responsive styling)
- **Chart.js** (charting via Symfony UX Chartjs)
- **HTML5 Forms** (filtering and selection)

## Data Sources

The web interface can load data from:

### Local Files (New - Works Now!)
Place JSON files in the `runs` directory:
```
application/
  runs/
    dataset1.json
    dataset2.json
    dataset3.json
```

Or mount the directory in Docker:
```bash
docker run -p 8000:80 \
  -v /path/to/your/json/files:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```

### AWS S3 (Requires credentials)
If AWS credentials are configured, datasets are automatically fetched from S3:
```bash
docker run -p 8000:80 \
  -e AWS_S3_BUCKET=bucket-name \
  -e AWS_REGION=us-east-1 \
  -e AWS_ACCESS_KEY=... \
  -e AWS_ACCESS_SECRET_KEY=... \
  moodlehq/moodle-bench-diff:latest
```

## Step-by-Step Usage Example

### 1. Start the Web Server
```bash
# Using Docker (simplest)
docker run -p 8000:80 \
  -v /path/to/your/benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```

### 2. Open Browser
Navigate to: `http://localhost:8000`

You'll see the dashboard with:
- Dataset filter form
- List of all available datasets
- Dataset comparison selector

### 3. Filter Datasets (Optional)
- Select branch, size, users, etc.
- Click submit to filter the list

### 4. Select Datasets to Compare
- Check the datasets you want to compare
- Submit the comparison form

### 5. View Results
The page will display:
- Comparison information table
- Multiple interactive bar charts
- One chart for each metric being compared

### 6. Analyze Charts
- Hover over bars to see values
- Compare performance visually
- Identify improvements or regressions

## Chart Types

The web interface displays **Bar Charts** (horizontal orientation) for:
- **dbreads**: Database read count
- **dbwrites**: Database write count
- **dbquerytime**: Total database query time
- **memoryused**: Memory usage in MB
- **filesincluded**: Number of files included
- **serverload**: Server load average
- **sessionsize**: Session size in KB
- **bytes**: HTTP bytes transferred
- **time**: Response time in milliseconds
- **latency**: Request latency

Each metric shows data for all selected datasets side-by-side for easy comparison.

## Features by Metric Type

### Performance Metrics
- Visual comparison of execution times
- Database operation counts
- Query time analysis

### Resource Metrics
- Memory usage comparison
- File include counts
- Server load visualization

### Network Metrics
- Bytes transferred
- Response latency
- Overall response time

## Customization

The templates are located in:
```
application/templates/
  base.html.twig              # Base layout
  index/
    index.html.twig           # Dashboard/comparison view
  comparison/
    compare.html.twig         # Comparison details
```

You can customize:
- Chart styling
- Color schemes
- Layout and themes
- Filter options
- Display formats

## Browser Compatibility

The web interface works in all modern browsers:
- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers

The interface is responsive and works on:
- Desktop computers
- Tablets
- Mobile phones

## Performance

The web interface is optimized for:
- Large datasets (handles hundreds of benchmarks)
- Quick filtering and chart generation
- Responsive UI even with many datasets
- Efficient rendering with Chart.js

## Security

The web interface includes:
- CSRF protection via Symfony
- Secure form handling
- Input validation
- Safe template rendering with Twig

## Architecture

```
Browser
   ↓
IndexController (GET /)
   ↓
DatasetLoader (local files or S3)
   ↓
Twig Templates (render HTML)
   ↓
Chart.js (render charts)
   ↓
Browser displays interactive page
```

## Troubleshooting

### No datasets showing
- Check that `runs` directory exists and contains JSON files
- Or configure AWS credentials if using S3
- Verify file permissions

### Charts not rendering
- Check browser console for errors
- Ensure Chart.js is loaded
- Verify dataset format is correct

### Filtering not working
- Refresh the page
- Check that datasets have the filter criteria set
- Verify form submission

## Summary

✅ **Yes, the tool has a professional web interface with:**
- Interactive dashboard
- Visual bar charts for each metric
- Advanced filtering and selection
- Real-time data loading
- Support for local files and AWS S3
- Mobile-responsive design
- Production-ready implementation

Start the web server and visit `http://localhost:8000` to begin visualizing your performance benchmarks!

