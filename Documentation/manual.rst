Extension Documentation "Amazing LESS"
======================================

.. sectionauthor:: Benni Mack <benni@typo3.org>

What Does It Do?
----------------

This extension compiles .less files (see http://lesscss.org/) into .css files on page hit by
hooking into the page loading process. The parsing and compiling is done by the 3rd party tool
LESSPHP (http://leafo.net/lessphp/). The extension allows .less files to be included in TYPO3's
TypoScript code, and in the PHP code of your extension, if you use the TYPO3 Page Renderer.


Why Is It So Amazing?
---------------------

Well... the key to all "amazing" extensions is that they are supereasy to set up and
to configure. We at b13 want to make life easy, and configure as little as possible for most of our
TYPO3 installations, but also have the possibility to configure, if needed.


Configuration
-------------

Install the extension and then include your .less file through the pageRenderer addCSSfile() function.

In the frontend, you can include the .less file via TypoScript. On my site, it looks like this:

.. code-block:: TypoScript

	page.includeCSS.file1 = fileadmin/main/templates/less/style.less

By default, the output file will be stored as a temporary CSS file under typo3temp/amazingless_*.css.

As with the default LESS PHP functionality, the file checks if the created .css file is older than the
.less file. If so, the .less file is re-compiled and the .css file is overwritten.

Please note that the LESS PHP compiler has certain limitations to date, especially when it comes to relative
paths in the image files. So make sure that the paths to image files are a variable, and are relative
to the final CSS file location.

.. tip::

	If you have a folder structure like "fileadmin/templates/less/style.less" and your 
	css output folder is located at "fileadmin/templates/css/" (that is ../css/ relative
	to your less/ directory), then output file will be put into css/style.css.
	As CodeKit and others do that, we recommend this for now as well (as long)



Thanks / Contributions
----------------------

Thanks go to

* the authors of LESS, LESSPHP, Twitter Bootstrap
* Dave Williams from Gold Prairie who introduced me to all this stuff
* The crew at b13, making use of these features
* Jesus Christ, who saved my life.


2012-05-14, Benni.