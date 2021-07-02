#!/bin/sh -x
bin/phpcs --report=full --report-file=./report.txt -p ./ || exit 1;
bin/roadiz lint:twig || exit 1;
bin/roadiz lint:twig src/Roadiz/Webhook/Resources/views || exit 1;
bin/roadiz lint:twig themes/Install/Resources/views || exit 1;
bin/roadiz lint:twig themes/Rozier/Resources/views || exit 1;
