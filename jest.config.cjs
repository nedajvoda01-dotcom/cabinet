// cabinet/jest.config.cjs
module.exports = {
  testEnvironment: "node",
  testMatch: [
    "<rootDir>/tests/**/*.test.ts",
    "<rootDir>/tests/**/*.test.js",
    "<rootDir>/tests/**/*.test.php"
  ],
  transform: {
    "^.+\\.ts$": ["ts-jest", { tsconfig: "<rootDir>/tsconfig.json" }],
  },
  moduleFileExtensions: ["ts", "js", "json"],
  verbose: true,
};
