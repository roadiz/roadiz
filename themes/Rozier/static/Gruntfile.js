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
			vendor:{
				'src': [
					'bower_components/uikit/js/uikit.js',
					'js/vendor/addons/sortable.js',
					'bower_components/uikit/js/components/nestable.js',
					'bower_components/uikit/js/components/pagination.js',
					'bower_components/uikit/js/components/notify.js',
					'bower_components/uikit/js/components/tooltip.js',
					'bower_components/jquery-ui/jquery-ui.js',
					'bower_components/gsap/src/uncompressed/TweenMax.js',
					'bower_components/bootstrap-switch/dist/js/bootstrap-switch.js',
					'bower_components/jquery-minicolors/jquery.minicolors.js',
					'bower_components/mousetrap/mousetrap.js',
					'bower_components/codemirror/lib/codemirror.js',
					'bower_components/codemirror/mode/markdown/markdown.js',
					'bower_components/codemirror/addon/mode/overlay.js',
					'bower_components/codemirror/mode/xml/xml.js',
					'bower_components/codemirror/mode/gfm/gfm.js',
					'bower_components/marked/lib/marked.js',
					'bower_components/dropzone/dist/dropzone.js',
					'js/vendor/ScrollToPlugin.js',
					'js/vendor/addons/htmleditor.js'
				],
				dest: 'dist/<%= pkg.name %>-vendor.js',
			},
			rezozero:{
				'src': [
					'js/trees/nodeTreeContextActions.js',
					'js/bulk-edits/documentsBulk.js',
					'js/bulk-edits/nodesBulk.js',
					'js/widgets/documentsList.js',
					'js/widgets/documentWidget.js',
					'js/widgets/nodeWidget.js',
					'js/widgets/customFormWidget.js',
					'js/widgets/documentUploader.js',
					'js/widgets/saveButtons.js',
					'js/widgets/settingsSaveButtons.js',
					'js/widgets/nodeEditSource.js',
					'js/widgets/nodeTree.js',
					'js/widgets/nodeStatuses.js',
					'js/widgets/geotagField.js',
					'js/widgets/childrenNodesField.js',
					'js/widgets/markdownEditor.js',
					'js/widgets/tagAutocomplete.js',
					'js/widgets/folderAutocomplete.js',
					'js/widgets/stackNodeTree.js',
					'js/node-type-fields/nodeTypeFieldsPosition.js',
					'js/node-type-fields/nodeTypeFieldEdit.js',
					'js/custom-form-fields/customFormFieldsPosition.js',
					'js/custom-form-fields/customFormFieldEdit.js',
					'js/panels/entriesPanel.js',
					'js/rozierMobile.js',
					'js/lazyload.js',
					'js/plugins.js',
					'js/main.js'
				],
				dest: 'dist/<%= pkg.name %>.js',
			},
			simple:{
				'src': [
					'bower_components/uikit/js/uikit.js',
					'js/login/login.js'
				],
				dest: 'dist/<%= pkg.name %>-simple.js',
			},
			cforms:{
				'src': [
					'bower_components/uikit/js/uikit.js',
					'bower_components/jquery-ui/jquery-ui.js'
				],
				dest: 'dist/<%= pkg.name %>-cforms.js',
			}
		},
		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd HH:MM:ss") %> */\n'
			},
			vendor: {
				src: 'dist/<%= pkg.name %>-vendor.js',
				dest: 'dist/<%= pkg.name %>-vendor.min.js'
			},
			rezozero: {
				src: 'dist/<%= pkg.name %>.js',
				dest: 'dist/<%= pkg.name %>.min.js'
			},
			simple: {
				src: 'dist/<%= pkg.name %>-simple.js',
				dest: 'dist/<%= pkg.name %>-simple.min.js'
			},
			cforms: {
				src: 'dist/<%= pkg.name %>-cforms.js',
				dest: 'dist/<%= pkg.name %>-cforms.min.js'
			}
		},
		less: {
			development: {
				options: {
					compress: false,
					yuicompress: false,
					optimization: 3,
					sourceMap: true
				},
				files:
				{
					"css/vendor.min.css" : "css/vendor.less",
				 	"css/style.min.css" : "css/style.less",
				 	"css/custom-forms-front.min.css" : "css/custom-forms-front.less"
				}
			},
			production: {
				options: {
					compress: true,
					yuicompress: true,
					optimization: 3,
					sourceMap: false
				},
				files:
				{
					"css/vendor.min.css" : "css/vendor.less",
				 	"css/style.min.css" : "css/style.less",
				 	"css/custom-forms-front.min.css" : "css/custom-forms-front.less"
				}
			}
		},
		watch: {
			scripts: {
				files: [
					'js/**/*.js',
					'!js/<%= pkg.name %>.js',
					'!js/<%= pkg.name %>.min.js',
					'!dist/<%= pkg.name %>.js',
					'!dist/<%= pkg.name %>.min.js',
					'css/**/*.less',
					'src-img/*.{png,jpg,gif}'
				],
				tasks: ['clean', 'less:development', 'jshint', 'concat','uglify', 'versioning'],
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
				'!dist/<%= pkg.name %>*.js',
				'!js/<%= pkg.name %>*.js'
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
				},
				{
					assets: [{
			            src: [ 'dist/<%= pkg.name %>-vendor.min.js' ],
			            dest: 'dist/<%= pkg.name %>-vendor.min.js'
			        }],
					key: 'global',
					dest: '',
					type: 'js',
					ext: '.min.js'
				},
				{
					assets: [{
			            src: [ 'css/vendor.min.css' ],
			            dest: 'css/vendor.min.css'
			        }],
					key: 'global',
					dest: '',
					type: 'css',
					ext: '.css'
				},
				{
					assets: [{
			            src: [ 'css/style.min.css' ],
			            dest: 'css/style.min.css'
			        }],
					key: 'global',
					dest: '',
					type: 'css',
					ext: '.css'
				},
				/*
				 * Simple layout for login
				 * versioned files
				 */
				{
					assets: [{
			            src: [ 'dist/<%= pkg.name %>-simple.min.js' ],
			            dest: 'dist/<%= pkg.name %>-simple.min.js'
			        }],
					key: 'simple',
					dest: '',
					type: 'js',
					ext: '.min.js'
				},
				/*
				 * Custom form versioned files
				 */
				{
					assets: [{
			            src: [ 'css/vendor.min.css' ],
			            dest: 'css/vendor.min.css'
			        }],
					key: 'custom-forms',
					dest: '',
					type: 'css',
					ext: '.css'
				},
				{
					assets: [{
			            src: [ 'css/custom-forms-front.min.css' ],
			            dest: 'css/custom-forms-front.min.css'
			        }],
					key: 'custom-forms',
					dest: '',
					type: 'css',
					ext: '.css'
				},
				{
					assets: [{
			            src: [ 'dist/<%= pkg.name %>-cforms.min.js' ],
			            dest: 'dist/<%= pkg.name %>-cforms.min.js'
			        }],
					key: 'custom-forms',
					dest: '',
					type: 'js',
					ext: '.min.js'
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
			grunt.config('watch.scripts.tasks', ['clean', 'jshint', 'concat:rezozero', 'uglify:rezozero', 'versioning']); // 'uglify',
		}
		else if(filepath.indexOf('.less') > -1 ){
			grunt.config('watch.scripts.tasks', ['clean','less:development', 'versioning']);
		}
		else if( filepath.indexOf('.png') > -1  ||
			filepath.indexOf('.jpg') > -1  ||
			filepath.indexOf('.gif') > -1 ){
			grunt.config('watch.scripts.tasks', ['imagemin']);
		}
	});

	// Default task(s).
	grunt.registerTask('default', ['clean','jshint','concat','uglify','less:production','imagemin','versioning']);
};