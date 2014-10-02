module.exports = function(grunt) {

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
					"js/vendor/mousetrap.min.js",
					"js/addons/sortable.min.js",
					"js/addons/nestable.min.js",
					"js/addons/pagination.min.js",
					"js/addons/notify.min.js",
					"js/vendor/codemirror.js",
					"js/vendor/mode/markdown/markdown.js",
					"js/vendor/mode/overlay.js",
					"js/vendor/mode/xml/xml.js",
					"js/vendor/mode/gfm/gfm.js",
					"js/vendor/marked.min.js",
					"js/addons/htmleditor.min.js",
					"js/vendor/dropzone.min.js",

					"js/widgets/documentWidget.js",
					"js/widgets/saveButtons.js",
					"js/widgets/nodeStatuses.js",
					"js/plugins.js",
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
				optimization: 3
			},
			files: {
				src : "css/style.less",
				dest : "css/style.min.css"
			}
		},
		watch: {
			scripts: {
				files: [
					'js/*.js',
					'js/*/*.js',
					'css/*.less',
					'css/**/*.less',
					'src-img/*.{png,jpg,gif}'
				],
				tasks: ['less', 'jshint', 'concat','uglify'],
				options: {
					event: ['added', 'deleted', 'changed'],
				},
			},
		},
		jshint: {
			all: [
				'Gruntfile.js',
				'js/*.js',
				'js/*/*.js',
				'!js/vendor/*.js',
				'!js/addons/*.js'
			]
		},
		imagemin: {
			dynamic: {
				options: {                       // Target options
					optimizationLevel: 4,
				},                       // Another target
				files: [{
					expand: true,                  // Enable dynamic expansion
					cwd: 'src-img/',               // Src matches are relative to this path
					src: ['**/*.{png,jpg,gif}'],   // Actual patterns to match
					dest: 'img/'                  // Destination path prefix
				}]
			}
		},
		phplint: {
			core: [
				"../*.php",
				"../*/*.php"
			]
		}
	});

	/*
	 * Watch differently LESS and JS
	 */
	grunt.event.on('watch', function(action, filepath) {
		if (filepath.indexOf('.js') > -1 ) {
			grunt.config('watch.scripts.tasks', ['jshint', 'concat','uglify']);
		}
		else if(filepath.indexOf('.less') > -1 ){
			grunt.config('watch.scripts.tasks', ['less']);
		}
		else if( filepath.indexOf('.png') > -1  ||
            filepath.indexOf('.jpg') > -1  ||
            filepath.indexOf('.gif') > -1 ){
            grunt.config('watch.scripts.tasks', ['imagemin']);
        }
	});

	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-imagemin');

	// Default task(s).
	grunt.registerTask('default', ['concat','uglify','less']);
};