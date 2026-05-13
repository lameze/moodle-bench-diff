# Web Server Port Configuration Guide

## Quick Start with Port 8080

Since you have Apache running on port 80, use port 8080 instead:

```bash
docker run --rm -p 8080:80 \
  -v ./benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```

Then open your browser to: **http://localhost:8080**

## Port Options

### Port 8080 (Recommended)
```bash
docker run --rm -p 8080:80 \
  -v ./benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```
- Good choice
- Less likely to be in use
- Easy to remember

### Port 9000
```bash
docker run --rm -p 9000:80 \
  -v ./benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```
- Alternative if 8080 is busy
- Also a common development port

### Port 3000
```bash
docker run --rm -p 3000:80 \
  -v ./benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```
- Another alternative
- Less common for web servers

## Understanding Port Mapping

The Docker port mapping syntax is: `-p HOST_PORT:CONTAINER_PORT`

- `8080:80` means: Access via `localhost:8080`, Docker container uses port 80 internally
- Your Apache: Uses port 80 on the host machine
- Both can coexist because they use different host ports

## Check Which Ports Are Available

To check if a port is free:

```bash
# Check port 8080
sudo lsof -i :8080

# Check port 9000
sudo lsof -i :9000

# Check port 3000
sudo lsof -i :3000
```

If the command returns nothing, the port is free.

## Full Command with All Options

```bash
docker run --rm \
  -p 8080:80 \
  -v ./benchmarks:/var/www/runs \
  moodlehq/moodle-bench-diff:latest
```

**Flags explained:**
- `--rm` - Remove container when it stops
- `-p 8080:80` - Map port 8080 (host) to 80 (container)
- `-v ./benchmarks:/var/www/runs` - Mount local directory
- `moodlehq/moodle-bench-diff:latest` - Image to run

## Stopping the Server

Press **Ctrl+C** in the terminal where Docker is running.

## Keeping Apache and Using Moodle Bench Diff

Since you want to keep Apache running on port 80, always use a different port for the moodle-bench-diff Docker container.

**Best Practice:**
- Apache: Port 80 (production/main site)
- Moodle Bench Diff: Port 8080 (development/testing)

This way both can run simultaneously without conflicts.

## Quick Commands

```bash
# Start on port 8080 (Recommended)
docker run --rm -p 8080:80 -v ./benchmarks:/var/www/runs moodlehq/moodle-bench-diff:latest

# Start on port 9000 (Alternative)
docker run --rm -p 9000:80 -v ./benchmarks:/var/www/runs moodlehq/moodle-bench-diff:latest

# Stop (Ctrl+C in the terminal)
# Or kill the container:
docker ps  # Find container ID
docker stop <container-id>
```

## Testing Your Setup

1. Keep Apache running: `sudo systemctl status apache2`
2. Start moodle-bench-diff on port 8080
3. Visit both in your browser:
   - Apache site: http://localhost/
   - Moodle Bench Diff: http://localhost:8080

Both will work simultaneously!

---

Use **Port 8080** - Go ahead and try it now! 🚀

