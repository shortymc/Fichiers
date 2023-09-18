This version renamed "Fichiers" is forked and translated to french by shorty @ tradezone.fr
vous pouvez télécharger l'album de shortymc à cette adresse : https://www.tradezone.fr/smc.zip
*CuteViewer* is a simple file/directory viewer made in PHP for my personal use. Since some people have shown interest in using it, I decided to distribute the source.
Most features require JavaScript enabled to function properly.

## Features
- Password protection for deletion/renaming of files
- The script is constrained to the directory it's put in and directories below it.
- View file/directory listings
- Hiding files/directories from being listed
- Rename files/directories
- Delete files
- Delete directories recursively
- Upload several files
- Different colors for the interface (the CSS stylesheet is generated dynamically)


## Install instructions
- Download zip/tarball and extract.
- CHMOD 777 recursively the folders and files where you need to upload some files
- Open up `index.php` in your favorite text editor.
- Change the `$pass` variable.
- Add the files/directories you want to hide to the `$hiddenDirs` array.
- Change/add any color you want to the `$colors` array. Each array key is a hex color in the `RRGGBB` or `RGB` format, which `fstyle.php` uses to generate a stylesheet using that color.
- Upload to your webserver or use it locally
  
## License
GPLv2
