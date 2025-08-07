module.exports = {
  apps: [
    {
      name: "server",
      script: "php",
      args: "artisan serve",
      interpreter: "php",
      watch: false
    }
  ]
}
