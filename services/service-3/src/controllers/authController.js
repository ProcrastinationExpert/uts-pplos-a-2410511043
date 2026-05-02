const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");
const axios = require("axios");
const db = require("../config/db");

// --- Fungsi Bantuan ---
const generateToken = (user) => {
  return jwt.sign(
    { id: user.id, email: user.email, role: user.role },
    process.env.JWT_SECRET,
    { expiresIn: process.env.JWT_EXPIRES_IN },
  );
};

// --- 1. Register Manual ---
exports.register = async (req, res) => {
  const { name, email, password } = req.body;
  try {
    const [existingUser] = await db.query(
      "SELECT * FROM users WHERE email = ?",
      [email],
    );
    if (existingUser.length > 0)
      return res.status(400).json({ message: "Email sudah terdaftar" });

    const hashedPassword = await bcrypt.hash(password, 10);
    await db.query(
      "INSERT INTO users (name, email, password) VALUES (?, ?, ?)",
      [name, email, hashedPassword],
    );

    res.status(201).json({ message: "Registrasi berhasil" });
  } catch (error) {
    res
      .status(500)
      .json({ message: "Terjadi kesalahan server", error: error.message });
  }
};

// --- 2. Login Manual ---
exports.login = async (req, res) => {
  const { email, password } = req.body;
  try {
    const [users] = await db.query("SELECT * FROM users WHERE email = ?", [
      email,
    ]);
    if (users.length === 0)
      return res.status(404).json({ message: "Email tidak ditemukan" });

    const user = users[0];
    if (!user.password)
      return res
        .status(400)
        .json({ message: "Gunakan login GitHub untuk akun ini" });

    const isMatch = await bcrypt.compare(password, user.password);
    if (!isMatch) return res.status(401).json({ message: "Password salah" });

    const token = generateToken(user);
    res.status(200).json({
      message: "Login berhasil",
      token,
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
      },
    });
  } catch (error) {
    res
      .status(500)
      .json({ message: "Terjadi kesalahan server", error: error.message });
  }
};

// --- 3. Dapatkan URL Login GitHub ---
exports.githubLoginUrl = (req, res) => {
  const githubAuthUrl = `https://github.com/login/oauth/authorize?client_id=${process.env.GITHUB_CLIENT_ID}&redirect_uri=${process.env.GITHUB_CALLBACK_URL}&scope=user:email`;
  res.status(200).json({ url: githubAuthUrl });
};

// --- 4. Callback GitHub OAuth ---
exports.githubCallback = async (req, res) => {
  const { code } = req.query;
  try {
    // Tukar code dengan Access Token
    const tokenResponse = await axios.post(
      "https://github.com/login/oauth/access_token",
      {
        client_id: process.env.GITHUB_CLIENT_ID,
        client_secret: process.env.GITHUB_CLIENT_SECRET,
        code: code,
      },
      { headers: { accept: "application/json" } },
    );

    const accessToken = tokenResponse.data.access_token;
    if (!accessToken)
      return res
        .status(400)
        .json({ message: "Gagal mendapatkan token dari GitHub" });

    // Ambil data user dari GitHub
    const userResponse = await axios.get("https://api.github.com/user", {
      headers: { Authorization: `Bearer ${accessToken}` },
    });

    const githubData = userResponse.data;
    const email = githubData.email || `${githubData.login}@github.com`; // Fallback jika email di-private

    // Cek atau buat user di DB
    let [users] = await db.query(
      "SELECT * FROM users WHERE github_id = ? OR email = ?",
      [githubData.id, email],
    );
    let user;

    if (users.length === 0) {
      // Buat user baru jika belum ada
      const [insertResult] = await db.query(
        "INSERT INTO users (name, email, github_id) VALUES (?, ?, ?)",
        [githubData.name || githubData.login, email, githubData.id],
      );
      user = {
        id: insertResult.insertId,
        name: githubData.name,
        email: email,
        role: "customer",
      };
    } else {
      user = users[0];
      // Update github_id jika login manual sebelumnya tapi email sama
      if (!user.github_id) {
        await db.query("UPDATE users SET github_id = ? WHERE id = ?", [
          githubData.id,
          user.id,
        ]);
      }
    }

    // Generate JWT
    const token = generateToken(user);
    res.status(200).json({
      message: "Login GitHub berhasil",
      token,
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
      },
    });
  } catch (error) {
    res.status(500).json({
      message: "Terjadi kesalahan saat autentikasi GitHub",
      error: error.message,
    });
  }
};

// --- 5. Ambil Profil User (Membutuhkan Token) ---
exports.getProfile = async (req, res) => {
  try {
    const [users] = await db.query(
      "SELECT id, name, email, role, github_id FROM users WHERE id = ?",
      [req.user.id],
    );
    if (users.length === 0)
      return res.status(404).json({ message: "User tidak ditemukan" });

    res.status(200).json({ data: users[0] });
  } catch (error) {
    res
      .status(500)
      .json({ message: "Terjadi kesalahan server", error: error.message });
  }
};
