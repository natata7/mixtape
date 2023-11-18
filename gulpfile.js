"use strict";
var gulp = require("gulp");
var wpPot = require("gulp-wp-pot");
var phpcs = require("gulp-phpcs");

gulp.task("make-pot", function () {
  return gulp
    .src("src/*.php")
    .pipe(
      wpPot({
        domain: "mixtape",
        package: "Mixtape",
      })
    )
    .pipe(gulp.dest("languages/mixtape.pot"));
});

gulp.task("phpcs", function () {
  return gulp
    .src(["src/**/*.php", "!src/vendor/**/*.*"])

    .pipe(
      phpcs({
        bin: "vendor/bin/phpcs",
      })
    )

    .pipe(phpcs.reporter("log"));
});
