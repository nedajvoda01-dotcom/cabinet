// tests/integrationTests/userApi.test.js
const request = require('supertest');
const app = require('../../backend/src/server');  // Assuming we export the Express app

describe('User API Tests', () => {
  it('should create a new user', async () => {
    const response = await request(app)
      .post('/api/users')
      .send({ name: 'Test User', email: 'testuser@example.com' });
      
    expect(response.status).toBe(201);
    expect(response.body).toHaveProperty('name', 'Test User');
  });

  it('should return all users', async () => {
    const response = await request(app)
      .get('/api/users');

    expect(response.status).toBe(200);
    expect(Array.isArray(response.body)).toBe(true);
  });
});
