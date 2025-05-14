#!/bin/bash

# Check if the correct number of arguments is provided
if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <version>"
    exit 1
fi

# Get the new version from the argument
NEW_VERSION=$1

# Validate the version format (simple semantic versioning check)
if ! [[ $NEW_VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Error: Version must be in the format X.Y.Z"
    exit 1
fi

# Get the current branch name
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

# Check if the current branch is master
if [ "$CURRENT_BRANCH" != "master" ]; then
    echo "Error: You must be on the 'master' branch to update the version."
    exit 1
fi

# Function to compare versions
version_compare() {
    local v1=$1
    local v2=$2
    IFS='.' read -r -a v1_parts <<< "$v1"
    IFS='.' read -r -a v2_parts <<< "$v2"

    for i in {0..2}; do
        if [ "${v1_parts[$i]}" -gt "${v2_parts[$i]}" ]; then
            return 1
        elif [ "${v1_parts[$i]}" -lt "${v2_parts[$i]}" ]; then
            return 2
        fi
    done
    return 0
}

# Get the current version from composer.json
CURRENT_COMPOSER_VERSION=$(jq -r '.version' composer.json)

# Check if jq command was successful
if [ $? -ne 0 ]; then
    echo "Error: Failed to read version from composer.json"
    exit 1
fi

# Compare the current version with the new version
version_compare "$CURRENT_COMPOSER_VERSION" "$NEW_VERSION"
case $? in
    0)
        echo "Error: The new version ($NEW_VERSION) is the same as the current version ($CURRENT_COMPOSER_VERSION)."
        exit 1
        ;;
    1)
        echo "Error: The current version ($CURRENT_COMPOSER_VERSION) is higher than the new version ($NEW_VERSION)."
        exit 1
        ;;
    2)
        # Proceed with the update
        ;;
esac

# Get the list of existing tags
EXISTING_TAGS=$(git tag)

# Check if the new version is higher than any existing tag
for TAG in $EXISTING_TAGS; do
    if [[ $TAG =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        version_compare "$TAG" "$NEW_VERSION"
        case $? in
            0)
                echo "Error: The new version ($NEW_VERSION) is the same as an existing tag ($TAG)."
                exit 1
                ;;
            1)
                echo "Error: An existing tag ($TAG) is higher than the new version ($NEW_VERSION)."
                exit 1
                ;;
            2)
                # Proceed with the update
                ;;
        esac
    fi
done


# Main checks pass; now execute!


# Update the version in composer.json
sed -i.bak "s/\"version\": \"[^\"]*\"/\"version\": \"$NEW_VERSION\"/" composer.json

# Check if the sed command was successful
if [ $? -ne 0 ]; then
    echo "Error: Failed to update composer.json"
    exit 1
fi

# Commit the change to composer.json
git add composer.json
git commit -m "Bump version to $NEW_VERSION"

# Create a new git tag with the version
git tag $NEW_VERSION

# Check if the tag was created successfully
if [ $? -ne 0 ]; then
    echo "Error: Failed to create tag"
    exit 1
fi

# Clean up the backup file
rm composer.json.bak

# Report success & last manual step
echo "Version updated to $NEW_VERSION and tagged successfully. You can push the changes with the following command:"
echo "  git push origin master --tags"
