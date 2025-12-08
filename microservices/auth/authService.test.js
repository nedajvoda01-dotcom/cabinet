// microservices/auth/authService.js
const jwt = require('jsonwebtoken');

const generateToken = (user) => {
  return jwt.sign({ id: user.id, name: user.name }, 'secretKey', { expiresIn: '1h' });
};

module.exports = { generateToken };
