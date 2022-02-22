"use strict";

const version = require("./package").version,
  gulp = require("gulp"),
  zip = require("gulp-zip");

gulp.task("copy", function () {
  return gulp
    .src(
      [
        "**/*",
        "!gulpfile.js",
        "!package*.json",
        "!dist",
        "!dist/**",
        "!node_modules",
        "!node_modules/**",
        "!**/.DS_Store",
      ],
      { base: "./src/pl_customergroups" }
    )
    .pipe(gulp.dest("./dist/pl_customergroups"));
});
gulp.task("zip", function () {
  return gulp
    .src(["./dist/**", "./dist/*.zip"])
    .pipe(zip("pl-customergroups-v" + version + ".zip"))
    .pipe(gulp.dest("./dist/"));
});
gulp.task("default", gulp.series("copy", "zip"));
