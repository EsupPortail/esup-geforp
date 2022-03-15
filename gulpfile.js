var gulp = require('gulp'),
    sass = require('gulp-sass'),
    concat = require('gulp-concat'),
    //notify = require('gulp-notify'),
    uglify = require('gulp-uglify'),
    cssmin = require('gulp-cssmin'),
    plumber = require('gulp-plumber'),
    ngAnnotate = require('gulp-ng-annotate'),
    stripDebug = require('gulp-strip-debug'),
    prefix = require('gulp-autoprefixer'),
    livereload = require('gulp-livereload'),
    templateCache = require('gulp-angular-templatecache');

var assets = require('./config/assets');

/***
 * Scripts
 */
gulp.task('scripts', ['templates'], function(){
    gulp.src(assets.scripts.scripts.concat(['public/build/templates.js']))
        .pipe(concat('scripts.js'))
        .pipe(gulp.dest('public/build'));

    gulp.src(assets.scripts.front)
        .pipe(concat('scss.js'))
        .pipe(gulp.dest('public/build'));

    //.pipe(notify({ message: 'scripted!' }));
});

gulp.task('uglify', ['scripts'], function() {
    return gulp.src('public/build/scripts.js')
        //.pipe(stripDebug())
        .pipe(ngAnnotate())
        .pipe(uglify({
            mangle: false
        }))
        .pipe(gulp.dest('public/build/'));
        //.pipe(notify({ message: 'uglified!' }));
});

gulp.task('scriptsDemo', ['uglify'], function(){
	return gulp.src(['public/build/scripts.js', 'demo/bootstrap-tour/build/js/bootstrap-tour.min.js', 'demo/feedback.js', 'demo/bootstrap-tour.js', 'demo/video.js'])
		.pipe(concat('scripts.js'))
		.pipe(gulp.dest('public/build'));
});

/***
 * CSS / Saas
 */

var sassTasks = [];
for (var key in assets.sass) {
    (function(){
        const _key = key;
        //Task for sass using libsass through gulp-sass
        gulp.task('sass.' + _key, function(){
            return gulp.src(assets.sass[_key])
                .pipe(plumber())
                .pipe(sass({
                    //sourceMap: 'sass',
                    //sourceComments: 'map'
                }))
                .pipe(prefix("last 1 version", "> 1%", "ie 8"))
                .pipe(concat( _key + '.css'))
                .pipe(gulp.dest('public/build'));
            //.pipe(notify({ message: 'stylized!' }));
        });
        sassTasks.push('sass.' + _key);
    }());
}
gulp.task('sass', sassTasks);


gulp.task('minify', ['sass'], function() {
	return gulp.src('public/build/styles.css')
		.pipe(cssmin({
			keepSpecialComments: 0
		}))
		.pipe(gulp.dest('public/build/'));
	//.pipe(notify({ message: 'minified!' }));
});

gulp.task('cssDemo', ['minify'], function(){
	return gulp.src(['public/build/styles.css', 'demo/bootstrap-tour/build/css/bootstrap-tour.min.css'])
		.pipe(concat('styles.css'))
		.pipe(gulp.dest('public/build'));
});

/***
 * Templates
 */
gulp.task('templates', function(){
    return gulp.src(assets.templates)
        .pipe(templateCache({
            module: 'conjecto.sygefor.app',
            base: function(file) {
                var path = file.path.replace(file.base, '');
                if (process.platform === 'win32') {
                    path = path.replace(/\\/g, '/');
                }
                //return path.replace(/^.+\/(\w+Bundle)\/Resources\/public\/ng\//g, '$1/');
                var regex = /^.+\/templates\/ng\/(.*)$/g;
                var result = regex.exec(path);
                if(result) {
                    return result[1].toLowerCase() + '/' + result[2];
                } else {
                    return path;
                }
            }
        }))
        .pipe(gulp.dest('public/build/'));

//    return gulp.src('templates/ng/**/*.html')
/*        .pipe(templateCache())
        .pipe(gulp.dest('public/build'));*/

/*    return gulp.src(assets.templates)
        .pipe(concat('templates.js'))
        .pipe(gulp.dest('public/build')); */

    //.pipe(notify({ message: 'templated!' }));
});

/**
 * Images
 */
/*gulp.task('images', function() {
    if(assets.images.length > 0) {
        return gulp.src(assets.images)
            //.pipe(imagemin({optimizationLevel: 5}))
            .pipe(gulp.dest('public_old/build'));
    }
});*/
gulp.task('images', function(){
    gulp.src(assets.images.favicon)
        .pipe(gulp.dest('public'));

    if(assets.images.img.length > 0) {
        gulp.src(assets.images.img)
            .pipe(gulp.dest('public/build'));
    }
});

/**
 * Fonts
 */
gulp.task('fonts', function () {
    gulp.src(assets.fonts)
        .pipe(gulp.dest('public/fonts'));
/*    return gulp.src('assets.fonts')
        .pipe(gulp.dest('public_old/build'))
        .pipe($.size());*/
});

/**
 * Watch
 */
gulp.task('watch', function() {
    livereload.listen();
    // scripts
    for (var key in assets.scripts) {
        (function() {
            const _key = key;
            gulp.watch(assets.scripts[_key], ['scripts']);
            gulp.watch('public/build/' + _key + '.js').on('change', livereload.changed);
        })();
    }

    // templates
//    gulp.watch(assets.templates, ['scripts']);
    // styles
    for (var key in assets.sass) {
        (function() {
            const _key = key;
            gulp.watch(assets.sass[_key], ['sass.' + _key]);
            gulp.watch('public/build/'+ _key +'.css').on('change', livereload.changed);
        })();
    }
});

/**
 * Serve
 */
gulp.task('serve', ['scripts', 'sass', 'images', 'watch', 'fonts']);

/**
 * Build
 */
gulp.task('build', ['uglify', 'minify', 'images', 'fonts']);

/**
 * Build Demo
 */
gulp.task('demo', ['scriptsDemo', 'cssDemo', 'images', 'fonts']);

/**
 * Default
 */
gulp.task('default', ['serve']);
