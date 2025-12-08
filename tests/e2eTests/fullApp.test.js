// tests/e2eTests/fullApp.test.js
const puppeteer = require('puppeteer');

describe('End-to-End Tests', () => {
  let browser;
  let page;

  beforeAll(async () => {
    browser = await puppeteer.launch({ headless: true });
    page = await browser.newPage();
  });

  afterAll(async () => {
    await browser.close();
  });

  it('should load the homepage', async () => {
    await page.goto('http://localhost:3000');
    const title = await page.title();
    expect(title).toBe('User List');
  });

  it('should add a user and display it', async () => {
    await page.goto('http://localhost:3000');
    await page.click('button');  // Assuming the button triggers fetch users
    const usersList = await page.$$eval('ul li', (users) => users.length);
    expect(usersList).toBeGreaterThan(0);  // Ensure at least one user is displayed
  });
});
