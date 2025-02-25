#!/bin/bash

# Define colors
red=$(tput -T xterm-256color setaf 1)
aqua=$(tput -T xterm-256color setaf 14)
lime=$(tput -T xterm-256color setaf 10)
yellow=$(tput -T xterm-256color setaf 11)
no_color=$(tput -T xterm-256color sgr0)

project_file="ddev_projects.txt"

if [ ! -f "$project_file" ]; then
    echo "${aqua}[Update ddev Projects] ${red}File $project_file not found. Please run the project discovery script first.${no_color}"
    exit 1
fi

read -rp "Do you want to automatically execute commands for all projects? (yes/no, default: no) " auto_run
auto_run=${auto_run:-no}

echo "${aqua}[Update ddev Projects] ${no_color}Starting project updates..."

while IFS=": " read -r project_name project_path; do
    if [ -z "$project_name" ] || [ -z "$project_path" ]; then
        continue
    fi

    echo "${aqua}[Update ddev Projects] ${lime}Processing project:${no_color} ${yellow}$project_name${no_color} at ${yellow}$project_path${no_color}"

    if [ "$auto_run" != "yes" ]; then
        read -rp "Proceed with updating $project_name? (yes/no, default: yes) " proceed
        proceed=${proceed:-yes}
        if [ "$proceed" != "yes" ]; then
            echo "${aqua}[Update ddev Projects] ${yellow}Skipping $project_name.${no_color}"
            continue
        fi
    fi

    if [ ! -d "$project_path" ]; then
        echo "${aqua}[Update ddev Projects] ${red}Project directory $project_path does not exist. Skipping.${no_color}"
        continue
    fi

    cd "$project_path" || {
        echo "${aqua}[Update ddev Projects] ${red}Failed to enter directory $project_path. Skipping.${no_color}"
        continue
    }

    echo "${aqua}[Update ddev Projects] ${no_color}Starting DDEV for $project_name..."
    ddev start

    echo "${aqua}[Update ddev Projects] ${no_color}Running auto-update for $project_name..."
    ddev auto-update

    echo "${aqua}[Update ddev Projects] ${no_color}Stopping DDEV for $project_name..."
    ddev stop

    echo "${aqua}[Update ddev Projects] ${lime}Finished processing $project_name.${no_color}"

done < "$project_file"

echo "${aqua}[Update ddev Projects] ${lime}All selected projects have been processed.${no_color}"
