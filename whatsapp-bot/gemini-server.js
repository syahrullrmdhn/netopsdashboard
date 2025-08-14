import express from 'express';
import dotenv from 'dotenv';
import fetch from 'node-fetch';

dotenv.config();

const app = express();
const port = 3001; // Kita gunakan port 3001 agar tidak bentrok

app.use(express.json());

app.post('/summarize', async (req, res) => {
    const { prompt } = req.body;

    if (!prompt) {
        return res.status(400).json({ error: 'Prompt is required' });
    }

    try {
        const GEMINI_API_KEY = process.env.GEMINI_API_KEY;
        const GEMINI_API_URL = `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=${GEMINI_API_KEY}`;        const apiResponse = await fetch(GEMINI_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                contents: [{ parts: [{ text: prompt }] }]
            })
        });

        if (!apiResponse.ok) {
            const errorText = await apiResponse.text();
            throw new Error(`Gemini API Error: ${apiResponse.status} ${errorText}`);
        }

        const data = await apiResponse.json();
        const summary = data.candidates[0].content.parts[0].text;
        
        res.json({ summary: summary });

    } catch (error) {
        console.error("Error in /summarize:", error);
        res.status(500).json({ error: 'Failed to generate summary from AI.' });
    }
});

app.listen(port, () => {
    console.log(`Node.js Gemini server running at http://localhost:${port}`);
});