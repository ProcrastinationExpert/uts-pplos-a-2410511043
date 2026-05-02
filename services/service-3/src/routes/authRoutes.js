const express = require("express");
const router = express.Router();
const authController = require("../controllers/authController.js");
const { verifyToken } = require("../middlewares/authMiddleware.js");

// Manual Auth
router.post("/register", authController.register);
router.post("/login", authController.login);

// GitHub OAuth
router.get("/github/url", authController.githubLoginUrl);
router.get("/github/callback", authController.githubCallback);

// Protected Route (Contoh pemanggilan API yang butuh JWT)
router.get("/profile", verifyToken, authController.getProfile);

module.exports = router;
