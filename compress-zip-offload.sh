#!/bin/bash
# Helps to either compress or offload via the help of PHP :)
# I promise you, I am not showing off, I go this route when I shuffle between Windows and MacOS to avoid unnecessary shell stress.
# @param -c To compress
# @param -o to offload

BRANCH=$(git rev-parse --abbrev-ref HEAD);
CLOSE_MSG="\n\nPress [Enter] to close window.";
CMD_USAGE="usage: cmd [-c] to compress or [-o] to offload";

if [[ "$BRANCH" != "main" ]]; then
  printf "Sorry fam, you can only zip and offload (deploy) on master branch, it kinda makes sense that way 😘.$CLOSE_MSG";
  read # Enter to close
  exit 1;

fi

while getopts ":oc" opt;
    do
        case ${opt} in
        c ) # Compress
            php cx-wp-plugin-deploy-helper.php --plugin_name=woo-korapay --ignore_file_path=.git,.wordpress-org,.vscode/,assets/js/src/,node_modules,vendor,.sh --delete_files_in_zip=cx-wp-plugin-deploy-helper.php,README.md,package-lock.json,composer.lock,phpcs.xml,.eslintrc.json,.distignore 
        ;;
        o ) # Offload to respective folder
            php cx-wp-plugin-deploy-helper.php --plugin_name=woo-korapay --offload=true
        ;;
        \? ) # Default
            printf "invalid arg options \n$CMD_USAGE"
        ;;
    esac
done

# Check if any arguments were passed
if [ $OPTIND -eq 1 ]; then
    printf "You must pass in an argument: \n$CMD_USAGE";
fi

shift $((OPTIND -1)) # clear

printf "$CLOSE_MSG";
read 
