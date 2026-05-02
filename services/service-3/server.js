require("dotenv").config();
const express = require("express");
const cors = require("cors");
const authRoutes = require("./src/routes/authRoutes.js");

const app = express();

app.use(cors());
app.use(express.json());

// Routes
app.use("/api/auth", authRoutes);

// Handling 404
app.use((req, res) => {
  res.status(404).json({ message: "Route not found" });
});

const PORT = process.env.PORT || 3001;
app.listen(PORT, () => {
  console.log(`User Service berjalan di port ${PORT}`);
});
