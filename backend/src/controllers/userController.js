// backend/src/controllers/userController.js
const users = [];  // Temporary user storage

// Get all users
exports.getUsers = (req, res) => {
  res.status(200).json(users);
};

// Create new user
exports.createUser = (req, res) => {
  const { name, email } = req.body;
  const newUser = { id: Date.now(), name, email };
  users.push(newUser);
  res.status(201).json(newUser);
};
