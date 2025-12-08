// tests/unitTests/userController.test.js
const userController = require('../../backend/src/controllers/userController');

test('createUser adds a new user to the users array', () => {
  const req = { body: { name: 'John Doe', email: 'john@example.com' } };
  const res = { status: jest.fn().mockReturnThis(), json: jest.fn() };

  userController.createUser(req, res);

  expect(res.status).toHaveBeenCalledWith(201);
  expect(res.json).toHaveBeenCalledWith(expect.objectContaining({ name: 'John Doe' }));
});

test('getUsers returns the correct list of users', () => {
  const req = {};
  const res = { status: jest.fn().mockReturnThis(), json: jest.fn() };

  // Simulate some users in the list
  userController.createUser({ body: { name: 'Jane Doe', email: 'jane@example.com' } }, res);
  
  userController.getUsers(req, res);

  expect(res.status).toHaveBeenCalledWith(200);
  expect(res.json).toHaveBeenCalledWith(expect.arrayContaining([
    expect.objectContaining({ name: 'Jane Doe' }),
  ]));
});
