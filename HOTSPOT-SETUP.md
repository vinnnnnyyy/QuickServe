# Hotspot Server Setup Guide

This guide will help you set up the Laravel application to run on a hotspot server, making it accessible to other devices on your network.

---

## Step 1: Find Your IP Address

Open Command Prompt and run:

```bash
ipconfig
```

Look for your active network adapter (usually "Wireless LAN adapter Wi-Fi" or "Ethernet adapter") and note the **IPv4 Address**. 

Example output:
```
Wireless LAN adapter Wi-Fi:
   IPv4 Address. . . . . . . . . . . : 192.168.8.125
```

Your IP address is: `192.168.8.125` (yours will be different)

---

## Step 2: Update .env File

Open `.env` file and update the `APP_URL`:

```env
APP_URL=http://YOUR_IP_ADDRESS:8000
```

Replace `YOUR_IP_ADDRESS` with the IP from Step 1.

Example:
```env
APP_URL=http://192.168.8.125:8000
```

---

## Step 3: Update vite.config.js

Open `vite.config.js` and update the `hmr.host`:

```javascript
server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
        host: 'YOUR_IP_ADDRESS',  // Update this line
        port: 5173
    }
}
```

Replace `YOUR_IP_ADDRESS` with the IP from Step 1.

Example:
```javascript
server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
        host: '192.168.8.125',
        port: 5173
    }
}
```

---

## Step 4: Clear Laravel Caches

Run these commands in your project directory:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

## Step 5: Start Laravel Server

Open **Terminal 1** and run:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

You should see:
```
INFO  Server running on [http://0.0.0.0:8000].
```

---

## Step 6: Start Vite Dev Server

Open **Terminal 2** and run:

```bash
npm run dev -- --host 0.0.0.0
```

You should see Vite running on port 5173.

---

## Step 7: Configure Firewall (If Needed)

Windows Firewall may prompt you to allow access:
- Click "Allow access" for both private and public networks
- Allow for both PHP and Node.js

---

## Step 8: Access from Other Devices

On any device connected to the **same WiFi network**, open a browser and go to:

```
http://YOUR_IP_ADDRESS:8000
```

Example:
```
http://192.168.8.125:8000
```

---

## Troubleshooting

### Changes not reflecting on hotspot devices?

1. **Hard refresh the browser:**
   - Chrome/Edge: `Ctrl + Shift + R` or `Ctrl + F5`
   - Mobile: Clear browser cache or use incognito mode

2. **Verify both servers are running:**
   - Terminal 1: Laravel server on port 8000
   - Terminal 2: Vite dev server on port 5173

3. **Check if IP address changed:**
   - Run `ipconfig` again
   - If IP changed, repeat Steps 2-4

### Cannot connect from other devices?

1. Make sure both devices are on the **same network**
2. Check Windows Firewall settings
3. Try disabling antivirus temporarily

### Error: "Failed to listen on IP address"

Your IP address has changed. Go back to Step 1 and get the new IP address.

---

## Notes

- The IP address `0.0.0.0` in the Laravel serve command means "listen on all network interfaces"
- Keep both terminal windows open while developing
- If you restart your computer or reconnect to WiFi, your IP might change - repeat the setup
- For production, use a proper web server like Apache or Nginx

---

## Quick Setup Commands

Once configured, you only need these two commands:

**Terminal 1:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Terminal 2:**
```bash
npm run dev -- --host 0.0.0.0
```
