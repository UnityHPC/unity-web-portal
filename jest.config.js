module.exports = {
  testEnvironment: "jsdom",
  testMatch: ["**/test/js/**/*.test.js"],
  setupFilesAfterEnv: ["<rootDir>/test/js/setup.js"],
  transform: {},
  moduleDirectories: ["node_modules"],
};
