var gulp = require("gulp"),
    templateCache = require('gulp-angular-templatecache');

gulp.task("tc", function() {
    return gulp
        .src("test.html")
        .pipe(templateCache()) // when I comment out this line I see test.html file is getting copied under dest folder
        .pipe(gulp.dest("dest"));
});