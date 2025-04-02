# wfo-image-cache
Minimalist IIIF level 0+ image server that caches submitted images and serves them for portal.

This is a very simple service designed to get us going with the least amount of code possible.

Please, write something more sophisticated to replace it!

# How it works

This is a cache for jpeg images that are, at least initially, available somewhere else on the internet. It implements a Level 0 IIIF Image API but with a couple of additions. This means that it could be replaced with a more capable IIIF server in the future with minimal changes to the portal software.

Images are identified by MD5 hashes of the full URI they were published on. The data flow is best explained with an example:

- This image https://botany.dnp.go.th/image/flora/8/EUPH_Fig60.jpg from the Flora of Thailand has the identifer ```e1d243cfa7ad6840f656660d0b9cc747``` which is MD5 hash of that URI.
- To call for a version of this image from the image server the URI would look like this ```https://<this-image-server>/e1d243cfa7ad6840f656660d0b9cc747/full/max/0/default.jpg```
- But how does the image server know about the image in the first place? An MD5 hash isn't reversible after all.
- If the server hasn't encountered the image identified by the MD5 before it will simply return HTTP NOT FOUND.
- Images are registered with the server by uploading a CSV file to the tool at index.php.
- This import of images is done as part of the preparation process for CSV files before they ingested into the Facet Service for inclusion in the portal.
- The same CSV files can be used to populated the images server as added to the Facet Service.



