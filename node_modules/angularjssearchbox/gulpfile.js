var gulp = require('gulp');

var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var sass = require('gulp-sass');
var minifyCSS = require('gulp-minify-css');
var templateCache = require('gulp-angular-templatecache');

var paths = {
  scripts: ['./src/js/**/*.js'],
  deps: [
//      "bower_components/angular-strap/dist/modules/tooltip.min.js",
//      "bower_components/angular-strap/dist/modules/tooltip.tpl.min.js",
      "bower_components/angular-strap/dist/modules/parse-options.min.js",
      "bower_components/angular-strap/dist/modules/dimensions.min.js"
  ],
  templates: ['./src/templates/**/*.html'],
  styles: ['./src/scss/**/*.{scss,css}']
};

gulp.task('scripts', function() {
  // Minify and copy all JavaScript (except vendor scripts)
  return gulp.src(paths.scripts)
        .pipe(concat('angularjssearchbox.js'))
        .pipe(gulp.dest('build'))
        .pipe(uglify())
        .pipe(concat('angularjssearchbox.min.js'))
        .pipe(gulp.dest('build'));
});

gulp.task('templates', function () {
    return gulp.src(paths.templates)
        .pipe(templateCache({
            filename: 'angularjssearchbox.tpl.js',
            root: 'templates/',
            module: 'angularjssearchbox'
        }))
        .pipe(gulp.dest('build'))
        .pipe(uglify())
        .pipe(concat('angularjssearchbox.tpl.min.js'))
        .pipe(gulp.dest('build'));
});

gulp.task('styles', function () {
    return gulp.src(paths.styles)
        .pipe(sass())
        .pipe(concat('angularjssearchbox.css'))
        .pipe(gulp.dest('build'))
        .pipe(minifyCSS())
        .pipe(concat('angularjssearchbox.min.css'))
        .pipe(gulp.dest('build'));
});

gulp.task('deps', function () {
    return gulp.src(
            paths.deps.concat([
                'build/angularjssearchbox.js',
                'build/angularjssearchbox.tpl.js'
            ])
        )
        .pipe(concat('angularjssearchbox.deps.js'))
        .pipe(gulp.dest('build'))
        .pipe(uglify())
        .pipe(concat('angularjssearchbox.deps.min.js'))
        .pipe(gulp.dest('build'));
});

gulp.task('serve', function(next) {
  var connect = require('connect'),
      server = connect();
  server.use(connect.static(".")).listen(process.env.PORT || 9000, next);
});

// Rerun the task when a file changes
gulp.task('watch', function() {
  gulp.watch(paths.scripts, ['scripts']);
  gulp.watch(paths.styles, ['styles']);
  gulp.watch(paths.templates, ['templates']);
});

// The default task (called when you run `gulp` from cli)
gulp.task('default', ['serve', 'scripts', 'templates', 'styles', 'deps', 'watch']);
