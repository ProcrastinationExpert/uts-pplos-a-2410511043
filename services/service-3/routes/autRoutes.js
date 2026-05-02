const express = require("express");
const router = express.Router();
const authController = require("../controllers/authController.js");
const { verifyToken } = require("../middlewares/authMiddleware.js");

router.post("/register", authController.register);
router.post("/login", authController.login);

router.get("/github/url", authController.githubLoginUrl);
router.get("/github/callback", authController.githubCallback);

router.get("/profile", verifyToken, authController.getProfile);

module.exports = router;
