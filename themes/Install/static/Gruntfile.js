module.exports = function(grunt) {
    require('jit-grunt')(grunt,
    {
        versioning: 'grunt-static-versioning'
    });
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        concat: {
            options: {
              separator: ';',
            },
            dist: {
                src: [
                    "js/vendor/bootstrap-switch.js",
                    "js/vendor/uikit.min.js",
                    "js/plugins.js",
                    "js/importFixtures.js",
                    "js/import.js",
                    "js/selectDatabaseField.js",
                    "js/resizeContainer.js",
                    "js/main.js"
                ],
                dest: 'dist/<%= pkg.name %>.js',
            },
        },
        uglify: {
          options: {
            banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd HH:MM:ss") %> */\n'
          },
          build: {
            src: 'dist/<%= pkg.name %>.js',
            dest: 'dist/<%= pkg.name %>.min.js'
          }
        },
        less: {
            options: {
                compress: true,
                yuicompress: true,
                optimization: 3,
                sourceMap: true
            },
            files: {
                src : "css/style.less",
                dest : "dist/style.css"
            }
        },
        watch: {
            scripts: {
                files: [
                    'js/*.js',
                    '!js/<%= pkg.name %>.js',
                    '!js/<%= pkg.name %>.min.js',
                    'css/**/*.less'
                ],
                tasks: ['less', 'jshint', 'concat','uglify', 'imagemin'],
                options: {
                    event: ['added', 'deleted', 'changed'],
                },
            },
        },
        jshint: {
            all: [
                'Gruntfile.js',
                'js/**/*.js',
                '!js/*.min.js',
                '!js/plugins.js',
                '!js/vendor/**/*.js',
                '!js/addons/**/*.js',
                '!js/<%= pkg.name %>.js',
                '!js/<%= pkg.name %>.min.js'
            ]
        },
        imagemin: {
            dynamic: {
                options: {                       // Target options
                    optimizationLevel: 4,
                },                       // Another target
                files: [{
                    expand: true,                  // Enable dynamic expansion
                    cwd: 'src-img/',                   // Src matches are relative to this path
                    src: ['**/*.{png,jpg,gif}'],   // Actual patterns to match
                    dest: 'img/'                  // Destination path prefix
                }]
            }
        },
        versioning: {
            options: {
                cwd: 'public',
                outputConfigDir: 'public/config',
                output: 'php'
            },
            dist: {
                files: [{
                    assets: [{
                        src: [ 'dist/<%= pkg.name %>.min.js' ],
                        dest: 'dist/<%= pkg.name %>.min.js'
                    }],
                    key: 'global',
                    dest: '',
                    type: 'js',
                    ext: '.min.js'
                }, {
                    assets: [{
                        src: [ 'dist/style.css' ],
                        dest: 'dist/style.css'
                    }],
                    key: 'global',
                    dest: '',
                    type: 'css',
                    ext: '.css'
                }]
            }
        },
        clean: ["public"]
    });

    /*
     * Watch differently LESS and JS
     */
    grunt.event.on('watch', function(action, filepath) {
        if (filepath.indexOf('.js') > -1 ) {
            grunt.config('watch.scripts.tasks', ['clean','jshint', 'concat', 'uglify', 'versioning']);
        }
        else if(filepath.indexOf('.less') > -1 ){
            grunt.config('watch.scripts.tasks', ['clean','less', 'versioning']);
        }
        else if( filepath.indexOf('.png') > -1  ||
            filepath.indexOf('.jpg') > -1  ||
            filepath.indexOf('.gif') > -1 ){
            grunt.config('watch.scripts.tasks', ['imagemin']);
        }
    });

    /* Using JIT to load tasks */
    // grunt.loadNpmTasks('grunt-contrib-jshint');
    // grunt.loadNpmTasks('grunt-contrib-watch');
    // grunt.loadNpmTasks('grunt-contrib-less');
    // grunt.loadNpmTasks('grunt-contrib-concat');
    // grunt.loadNpmTasks('grunt-contrib-uglify');
    // grunt.loadNpmTasks('grunt-contrib-imagemin');
    // grunt.loadNpmTasks("grunt-phplint");

    // Default task(s).
    grunt.registerTask('default', ['clean','jshint','concat','uglify','less','imagemin','versioning']);
};
