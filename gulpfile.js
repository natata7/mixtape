"use strict";
var gulp = require("gulp");
var wpPot = require("gulp-wp-pot");

gulp.task("make-pot", function () {
  return gulp
    .src("src/*.php")
    .pipe(
      wpPot({
        domain: "mistape",
        package: "Mistape",
      })
    )
    .pipe(gulp.dest("languages/mistape.pot"));
});
