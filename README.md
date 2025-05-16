# Subtitle Conversion Tool (Dockerized Fork)

This repository is a Dockerized fork of a subtitle conversion tool @ https://github.com/mantas-done/subtitles. The application is configured to perform weak conversions, adhering strictly to the output format with minimal edits to contents.

## Installation

To build the Docker image, run:

	```sh
		docker build -t php-subtitles .
	```
## Running the Application

To run the application and convert subtitles, use the following command:

	```sh
		docker run --rm -v $(pwd):/mnt php-subtitles <input-file> <output-file>
	```

## Additional Scripts

Some Additional Scripts are added to 
1. time shift and change to a specific format  
Forward TCIN shift for 3600 sec for a given file, the output will be added to the same location with same name.
```sh
	cd addl_scripts
	php shift_helper.php --input=../dummy_test.scc --shift=-3600 --output=srt 
	cd ..
```
