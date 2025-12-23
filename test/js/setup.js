/**
 * Jest setup file for JavaScript tests
 * Configure globals and polyfills needed for testing
 */

// Polyfill TextEncoder and TextDecoder for Node.js environment
const { TextEncoder, TextDecoder } = require("util");
global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder;

// Mock window.location if needed
if (!window.location.href) {
  delete window.location;
  window.location = new URL("http://localhost/");
}

// Suppress console errors during tests unless explicitly needed
const originalError = console.error;
beforeAll(() => {
  console.error = (...args) => {
    if (
      typeof args[0] === "string" &&
      args[0].includes("Not implemented: HTMLFormElement.prototype.submit")
    ) {
      return;
    }
    originalError.call(console, ...args);
  };
});

afterAll(() => {
  console.error = originalError;
});
