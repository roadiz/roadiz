#!/bin/sh -x
bin/phpcs --report=full --report-file=./report.txt -p ./;
bin/roadiz lint:twig;
bin/roadiz lint:twig themes/Install/Resources/views;
bin/roadiz lint:twig themes/Rozier/Resources/views;
