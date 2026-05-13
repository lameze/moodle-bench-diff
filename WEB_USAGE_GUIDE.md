# Using the Web Interface

## Quick Start - Access the Web Dashboard

### Method 1: Docker with Local Data (Recommended)

```bash
# Copy your benchmark JSON files to a directory
mkdir -p ./benchmarks
cp before.json ./benchmarks/
cp after.json ./benchmarks/

# Start the web server
docker run --rm -p 8080:80 \
  -v ./benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```

Then open your browser to: **http://localhost:8080**

**⚠️ Note on Port Conflicts:**
If you have Apache or another service running on port 80 or 8000, use port 8080 instead:
- Change `-p 8000:80` to `-p 8080:80`
- Or use any other free port like 9000
- See [PORT_CONFIGURATION.md](PORT_CONFIGURATION.md) for more details

### Method 2: Docker with AWS S3

```bash
docker run --rm -p 8000:80 \
  -e AWS_S3_BUCKET=my-bucket \
  -e AWS_REGION=us-east-1 \
  -e AWS_ACCESS_KEY=your-key \
  -e AWS_ACCESS_SECRET_KEY=your-secret \
  moodlehq/moodle-bench-diff:latest
```

Then open: **http://localhost:8000**

### Method 3: Local Development

```bash
cd application
composer install
symfony server:start
```

Open: **http://localhost:8000**

## Web Dashboard Overview

### Screen 1: Filter and Select Datasets

**Location:** Home page `/`

**What you see:**
- Filter options (dropdown menus)
- List of all available datasets
- Checkboxes to select which datasets to compare

**How to use:**
1. **Filter datasets** (optional):
   - Select a branch from "Branch" dropdown
   - Select server size (XS, S, M, L, XL)
   - Enter number of users
   - Enter loop count
   - Enter throughput value
   - Enter ramp-up time
   - Click "Submit" to filter

2. **Select datasets to compare**:
   - Check the boxes next to the datasets you want to compare
   - Click "Compare Selected" button

### Screen 2: Comparison Results

**What you see:**
- **Summary Table**: Shows configuration details of selected datasets
  - Moodle branch
  - Timestamp (when test was run)
  - Run description
  - Number of users
  - Site version
  - And more...

- **Performance Charts**: Multiple bar charts showing metrics
  - One chart for each metric type
  - Datasets displayed side-by-side
  - Easy visual comparison

**Charts include:**
- Database operations (reads, writes, query time)
- Memory metrics (memory used, session size)
- Network metrics (bytes, latency, response time)
- System metrics (files included, server load)

## Visual Chart Features

### How to Read the Charts

Each horizontal bar chart shows:
- **Y-axis labels**: Test scenarios (e.g., "Login", "View Course")
- **X-axis values**: Metric measurements
- **Colored bars**: Different datasets being compared
- **Hover info**: Exact values on hover

### Example: Database Query Time Chart

```
Scenario          Dataset 1    Dataset 2
─────────────────┴─────────┴──────────
Login                ▓▓▓▓      ▓▓▓
Frontpage logged    ▓▓▓▓▓     ▓▓▓▓
View course        ▓▓▓▓▓▓    ▓▓▓▓▓
View activity      ▓▓▓       ▓▓
```

Lower bars = better performance for most metrics

### Interactive Features

- **Hover over bars** to see exact values
- **Chart is responsive** - automatically fits screen size
- **Legend shows** which color represents which dataset
- **Mobile-friendly** - works on tablets and phones

## Typical Workflow

### Step 1: Start the Application
```bash
docker run --rm -p 8000:80 \
  -v ./benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```

### Step 2: Wait for Server
- Wait for the message "Web server listening"
- Takes about 30-60 seconds

### Step 3: Open Browser
- Go to: **http://localhost:8000**
- Page will load showing all available datasets

### Step 4: Filter (Optional)
If you have many datasets:
- Select branch/size/users filters
- Click Submit to narrow down options

### Step 5: Select Datasets
- Check boxes next to 2-4 datasets you want to compare
- Click "Compare Selected"

### Step 6: View Charts
The page will show:
- Summary information table
- Multiple performance charts
- Scroll to see all metrics

### Step 7: Analyze
- Compare performance across datasets
- Identify improvements/regressions
- Note which metrics changed

## Understanding the Metrics

### Database Metrics

**dbreads**
- Number of database read operations
- Lower is better
- Shows how many queries fetch data

**dbwrites**
- Number of database write operations
- Lower is better
- Shows how many updates/inserts are performed

**dbquerytime**
- Total time spent in database queries
- Lower is better
- Measured in seconds

### Resource Metrics

**memoryused**
- Memory consumed during test
- Lower is better
- Measured in MB

**filesincluded**
- Number of PHP files loaded
- Lower is better
- Affects startup time

**serverload**
- Average system load during test
- Lower is better
- CPU usage indicator

**sessionsize**
- Size of session data
- Lower is better
- Measured in KB

### Network Metrics

**bytes** (total and average)
- HTTP response size
- Lower is better
- Total bytes transferred

**latency**
- Time between request and first response byte
- Lower is better
- Measured in milliseconds

**time** (total and average)
- Overall response time
- Lower is better
- Measured in milliseconds

## Tips and Tricks

### Comparing Multiple Datasets
- Select 2-4 datasets for best visual comparison
- More than 4 datasets may be hard to read
- Can always select different combinations

### Identifying Regressions
1. Compare "before" and "after" benchmarks
2. Look for metrics that got **worse** (longer bars)
3. Note which scenarios were affected
4. Investigate code changes in those areas

### Spotting Improvements
1. Look for metrics with **shorter bars** in new version
2. Greatest improvements usually in:
   - Database query time
   - Memory usage
   - Response time

### Using with CI/CD
1. Run benchmarks as part of your CI pipeline
2. Save results to JSON files
3. Mount the files in the Docker container
4. Share the web dashboard link with team
5. Review visual comparison in pull requests

## Troubleshooting

### Datasets Not Showing
**Problem:** Homepage shows "No datasets available"

**Solutions:**
- Ensure `runs` directory exists and contains JSON files
- Check file permissions (should be readable)
- Verify files are valid JSON
- Check Docker volume mount is correct: `-v ./benchmarks:/var/www/runs`

### Charts Not Rendering
**Problem:** Charts appear blank or missing

**Solutions:**
- Check browser console for JavaScript errors (F12)
- Ensure Chart.js is loaded (check network tab)
- Verify dataset format is correct
- Try different browser
- Clear browser cache

### Server Won't Start
**Problem:** "Web server listening" never appears

**Solutions:**
- Check port 8000 isn't already in use
- Use different port: `-p 9000:80`
- Check Docker daemon is running
- Rebuild Docker image: `docker build -t ...`

### Slow Loading
**Problem:** Page takes long time to load

**Solutions:**
- Normal for first load (API downloads CDN resources)
- Subsequent loads will be faster
- More datasets = slower filtering
- Can reduce dataset count

## Browser Support

✅ **Fully supported:**
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)

✅ **Mobile browsers:**
- Works on tablets and phones
- Responsive design
- Touch-friendly

## Performance Notes

- **Fast filtering**: Thousands of datasets handled efficiently
- **Chart rendering**: Instant chart generation
- **Responsive**: UI remains responsive with many datasets
- **Mobile friendly**: Works seamlessly on mobile devices

## Customizing the Interface

The web interface can be customized by editing templates:

**Main files:**
- `application/templates/base.html.twig` - Overall layout
- `application/templates/index/index.html.twig` - Dashboard
- `application/assets/styles/app.css` - Styling

**Modifications you can make:**
- Change colors and themes
- Add/remove filter fields
- Customize chart colors
- Add additional information
- Modify table layouts

## Stopping the Server

Press **Ctrl+C** in the terminal where Docker is running.

Or kill the container:
```bash
docker ps  # find container ID
docker stop <container-id>
```

## Next Steps

1. ✅ Use the web interface for visual analysis
2. ✅ Share links with your team
3. ✅ Integrate into your CI/CD dashboard
4. ✅ Set up automated benchmark runs
5. ✅ Monitor performance trends over time

---

**Need help?** See the main README.md or CHANGELOG.md for more information.

