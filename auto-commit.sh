#!/bin/bash

# Auto-commit script for hourly commits
cd /home/forge/api.sweat93.gr

# Log file location
LOGFILE="/home/forge/api.sweat93.gr/auto-commit.log"

# Get current date and time
DATETIME=$(date '+%Y-%m-%d %H:%M:%S')

# Write to log that cron job started
echo "[$DATETIME] Auto-commit cron job started" >> "$LOGFILE"

# Check if there are any changes to commit
if ! git diff-index --quiet HEAD --; then
    # Add all changes
    git add -A
    
    # Commit with auto-commit message
    git commit -m "Auto commit - $DATETIME"
    
    # Push to remote repository
    if git push origin HEAD 2>&1; then
        echo "[$DATETIME] Successfully committed and pushed changes" >> "$LOGFILE"
        echo "Auto commit and push completed at $DATETIME"
    else
        echo "[$DATETIME] ERROR: Failed to push changes" >> "$LOGFILE"
        echo "Error: Failed to push changes at $DATETIME"
    fi
else
    echo "[$DATETIME] No changes to commit" >> "$LOGFILE"
    echo "No changes to commit at $DATETIME"
fi

# Add separator for readability
echo "----------------------------------------" >> "$LOGFILE"