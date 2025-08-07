import express from 'express';
import dotenv from 'dotenv';
import fetch from 'node-fetch';

dotenv.config();
const router = express.Router();

const { GEMINI_API_KEY } = process.env;
const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

export async function generateGeminiContent(prompt) {
  const res = await fetch(`${GEMINI_API_URL}?key=${GEMINI_API_KEY}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      contents: [
        { parts: [ { text: prompt } ] }
      ]
    })
  });

  if (!res.ok) throw new Error(`Gemini API error: ${res.status} ${await res.text()}`);
  const data = await res.json();

  return (
    data?.candidates?.[0]?.content?.parts?.[0]?.text ||
    '[no response from Gemini]'
  );
}

router.post('/story', async (req, res) => {
  const { prompt } = req.body;
  if (!prompt) return res.status(400).json({ error: 'Missing prompt' });

  try {
    const story = await generateGeminiContent(prompt);
    return res.json({ story });
  } catch (err) {
    console.error('Gemini error:', err);
    return res.status(500).json({ error: err.message });
  }
});

export default router;
