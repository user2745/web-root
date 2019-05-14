//Imports & Variables
const express = require('express');
const path = require('path');

let app = express();
let port = process.env.PORT || 3003;
let host = process.env.HOST || `localhost`;


//Serve static files
app.use(express.static('.'))
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, "/", "index.html"))
})

app.listen(port, (err) => {
    if (err) throw err
    console.log(`[Server] Live on ${host}:${port}`)
})