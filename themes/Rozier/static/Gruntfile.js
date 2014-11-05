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
				'src': [
					'js/vendor/jquery-ui.js',
					'js/vendor/TweenMax.min.js',
					'js/vendor/bootstrap-switch.js',
					'js/vendor/uikit.min.js',
					'js/vendor/mousetrap.min.js',
					'js/addons/sortable.min.js',
					'js/addons/nestable.js',
					'js/addons/pagination.min.js',
					'js/addons/notify.min.js',
					'js/vendor/jquery.minicolors.min.js',
					'js/vendor/codemirror.js',
					'js/vendor/mode/markdown/markdown.js',
					'js/vendor/mode/overlay.js',
					'js/vendor/mode/xml/xml.js',
					'js/vendor/mode/gfm/gfm.js',
					'js/vendor/marked.min.js',
					'js/addons/htmleditor.js',
					'js/vendor/dropzone.js',
					'js/widgets/documentWidget.js',
					'js/widgets/documentUploader.js',
					'js/widgets/saveButtons.js',
					'js/widgets/nodeStatuses.js',
					'js/widgets/childrenNodesField.js',
					'js/widgets/markdownEditor.js',
					'js/widgets/tagAutocomplete.js',
					'js/widgets/stackNodeTree.js',
					'js/widgets/nodeTypeFieldsPosition.js',
					'js/widgets/customFormFieldsPosition.js',
					'js/lazyload.js',
					'js/plugins.js',
					'js/main.js'
				],
				dest: 'js/<%= pkg.name %>.js',
			},
		},
		uglify: {
		  options: {
			banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd HH:MM:ss") %> */\n'
		  },
		  build: {
			src: 'js/<%= pkg.name %>.js',
			dest: 'js/<%= pkg.name %>.min.js'
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
					'js/**/*.js',
					'!js/<%= pkg.name %>.js',
					'!js/<%= pkg.name %>.min.js',
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
					cwd: 'src-img/',               // Src matches are relative to this path
					src: ['**/*.{png,jpg,gif}'],   // Actual patterns to match
					dest: 'img/'                  // Destination path prefix
				}]
			}
		},
		// phplint: {
		// 	core: [
		// 		"../*.php",
		// 		"../*/*.php"
		// 	]
		// }
		versioning: {
			options: {
				cwd: 'public',
				outputConfigDir: 'public/config',
				output: 'php'
			},
			dist: {
				files: [{
					assets: [{
			            src: [ 'js/<%= pkg.name %>.min.js' ],
			            dest: 'js/<%= pkg.name %>.min.js'
			        }],
					key: 'global',
					dest: '',
					type: 'js',
					ext: '.min.js'
				}, {
					assets: [{
			            src: [ 'css/style.min.css' ],
			            dest: 'css/style.min.css'
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

	// grunt.loadNpmTasks('grunt-contrib-jshint');
	// grunt.loadNpmTasks('grunt-contrib-watch');
	// grunt.loadNpmTasks('grunt-contrib-less');
	// grunt.loadNpmTasks('grunt-contrib-concat');
	// grunt.loadNpmTasks('grunt-contrib-uglify');
	// grunt.loadNpmTasks('grunt-contrib-imagemin');

	// Default task(s).
	grunt.registerTask('default', ['clean','jshint','concat','uglify','less','imagemin','versioning']);
};