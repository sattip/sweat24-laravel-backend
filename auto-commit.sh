#!/bin/bash

# Auto-commit script for hourly commits
cd /home/forge/api.sweat93.gr

# Check if there are any changes to commit
if ! git diff-index --quiet HEAD --; then
    # Get current date and time
    DATETIME=$(date '+%Y-%m-%d %H:%M:%S')
    
    # Add all changes
    git add -A
    
    # Commit with auto-commit message
    git commit -m "Auto commit - $DATETIME"
    
    echo "Auto commit completed at $DATETIME"
else
    echo "No changes to commit at $(date '+%Y-%m-%d %H:%M:%S')"
fi