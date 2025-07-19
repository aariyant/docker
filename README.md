# Docker
---

## Table of Contents
- [Installing Docker Engine](#installing-docker-engine)
- [Building Docker Images](#building-docker-images)
- [Manage Data with Docker Volumes](#manage-data-with-docker-volumes)
- [Docker Network (Cross-Container)](#docker-network-cross-container)
- [Docker Compose](#docker-compose)
- [Docker Commands](#docker-commands)
- [Docker Use Case with PHP Project](#docker-use-case-with-php-project)

---

## Installing Docker Engine

<details open>
<summary><b><font size="4">Linux</font></b></summary>
<p>

  You can follow this link for the instruction https://docs.docker.com/engine/install/ and select with your operating system.

</p>
</details>

<details open>
<summary><b><font size="4">Windows and MacOS</font></b></summary>
<p>

For Windows and MacOS docker can be installed with the docker installer, please follow the instruction for your operating system.

- __Windows__ - https://docs.docker.com/desktop/setup/install/windows-install/
- __MacOS__ - https://docs.docker.com/desktop/setup/install/mac-install/

</p>
</details>

---

## Building Docker Images

Docker images define the environment and configuration used to run your application. You can create custom images using a `Dockerfile`.


### What is a Dockerfile?

A Dockerfile is a plain text file containing a set of instructions used to build a Docker image. Each instruction defines a step in creating the image, such as selecting a base image (`FROM`), copying files (`COPY`), installing packages (`RUN`), or setting environment variables (`ENV`).


### Why is a Dockerfile needed?

A Dockerfile is needed to automate the creation of Docker images by defining the exact steps, configuration, and dependencies required for an application to run in a container. It ensures consistency across environments by scripting how the image is built, including OS base, package installations, environment variables, and entry points.


### Key Dockerfile Instructions

Docker supports over 15 different Dockerfile instructions for adding content to your image and setting configuration parameters. Each plays a distinct role in shaping the container’s behavior, layering, and configuration.

- `FROM`: Sets the base image (e.g., `FROM ubuntu:20.04`)
- `RUN`: Executes shell commands during build time
- `CMD`: Provides default command to run at container start
- `ENTRYPOINT`: Defines a fixed command, often combined with `CMD` for arguments
- `COPY` / `ADD`: Copies files into the image (`ADD` also supports URLs and archives)
- `ENV`: Sets environment variables
- `EXPOSE`: Documents which ports the container will listen on
- `WORKDIR`: Sets the working directory for following instructions
- `VOLUME`: Declares mount points for persistent or shared data
- `ARG`: Defines build-time variables, usable during `RUN`


### Example: Dockerfile for PHP + Apache

Let’s say you have a basic PHP web app with the following structure:

```
simple-php-app/
├── index.php
├── logs/
├── Dockerfile
```

**index\.php**

```php
<?php
$logPath = "logs/access.log";
file_put_contents($logPath, "[" . date("Y-m-d H:i:s") . "] Accessed\n", FILE_APPEND);
echo "Hello from PHP inside Docker!";
?>
```

**Dockerfile**

```Dockerfile
FROM php:8.2-apache
WORKDIR /var/www/html

# Copy all files
COPY . .

# Set write permissions for logs
RUN chown -R www-data:www-data /var/www/html/logs

EXPOSE 80
```

**Build and run the image:**

```bash
docker build -t simple-php-app .
docker run -p 8080:80 simple-php-app
```

Then open your browser to http://localhost:8080 and you should see:

```
Hello from PHP inside Docker!
```

***

### Dockerfile Best Practices

- Don’t use `latest` for your base images  
- Only use trusted base images (e.g. from Docker Hub official images)  
- Use `HEALTHCHECK` to enable container health monitoring  
- Set `ENTRYPOINT` and `CMD` correctly for flexibility and override support  
- Don’t hardcode secrets into your Docker images  
- Label your images (`LABEL`) for better metadata and automation  
- Set a non-root user (`USER`) to improve security  
- Use `.dockerignore` to avoid copying unnecessary files  
- Keep your images small by cleaning up and using slim base images

---

## Manage Data with Docker Volumes

Volumes let you persist data created by containers or share data between them. They are the preferred mechanism for managing data in Docker because they are designed to be independent of the container lifecycle.

***

### Types of Docker Volumes

Docker supports multiple volume types. Here's a breakdown:

- **Named Volumes**  
  Managed by Docker and stored under `/var/lib/docker/volumes/`. These are great for persistent data like databases or application files.

  ```bash
  docker volume create phplogs
  docker run -v phplogs:/var/www/html/logs -p 8080:80 simple-php-app
  ```

- **Bind Mounts**  
  Maps a specific directory on your host to the container. Ideal for local development to sync your code.

  ```bash
  docker run -v $(pwd)/logs:/var/www/html/logs -p 8080:80 simple-php-app
  ```

- **tmpfs Mounts**  
  Stores data only in memory. Useful for temporary files or sensitive information.

  ```bash
  docker run --tmpfs /var/www/html/tmp -p 8080:80 simple-php-app
  ```

***

### Comparison: Named Volumes vs Bind Mounts

| Named Volumes                                                                 | Bind Mounts                                                                  |
|------------------------------------------------------------------------------|------------------------------------------------------------------------------|
| Easy backups and recoveries                                                  | There is a bit of complexity involved in backup and recovery                |
| To mount it, we only need the volume name, not including paths               | It is necessary to provide a path to the host machine when mounting         |
| Containers can have volumes created while they are being created             | The mount folder will be created if it doesn't exist on the host            |
| Volumes are stored in `/var/lib/docker/volumes`                              | A bind mount can reside anywhere on the host machine                        |

***

### When to Use Docker Volumes

Docker volumes are suitable in the following scenarios:

- Database storage  
  Persist databases such as PostgreSQL or SQLite used by your app.

- Application data  
  Store logs, user uploads, or session files for your Flask app.

- Essential caches  
  Cache Python wheels or temporary build files.

- Convenient data backups  
  Create portable backups using `docker volume inspect` and `tar`.

- Share data between containers  
  e.g., share logs or socket files with an Nginx reverse proxy.

- Write to remote filesystems  
  Mount NFS or cloud storage volumes for distributed applications.

***

### Example: Add Log Volume to PHP App

**index.php**

```php
<?php
$logPath = __DIR__ . "/logs/access.log";

// Ensure the logs directory exists
if (!file_exists(dirname($logPath))) {
    mkdir(dirname($logPath), 0777, true);
}

// Write a log entry
file_put_contents($logPath, "[" . date("Y-m-d H:i:s") . "] Page visited\n", FILE_APPEND);

// Display the log content
echo "<h1>Access Log</h1>";
if (file_exists($logPath)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($logPath)) . "</pre>";
} else {
    echo "<p>No log file found.</p>";
}

```

**Dockerfile (same as before)**

```Dockerfile
FROM php:8.2-apache
WORKDIR /var/www/html
COPY . .
RUN chown -R www-data:www-data /var/www/html/logs
EXPOSE 80
```

**Run with docker volume for logs:**

```bash
docker volume create phplogs
docker build -t simple-php-app-with-volume .
docker run -p 8080:80 -v phplogs:/var/www/html/logs simple-php-app-with-volume
```

**Run with bind mount for live editing:**

```bash
docker run -p 8080:80 -v $(pwd):/var/www/html simple-php-app-with-volume
```

***

### Interacting With Docker Volumes

Here are useful commands for creating, inspecting, and managing Docker volumes:

- List all volumes

  ```bash
  docker volume ls
  ```

- Inspect a volume

  ```bash
  docker volume inspect phplogs
  ```

- Remove a specific volume

  ```bash
  docker volume rm phplogs
  ```

- Remove all unused volumes

  ```bash
  docker volume prune
  ```

  > ⚠️ `prune` deletes all **unused** volumes (not currently attached to any container). Use with care.

- Mount and explore a volume manually (for debugging)

  ```bash
  docker run -it --rm -v phplogs:/data alpine sh
  ls /data
  ```

---

## Docker Network (Cross-Container Communication)

Docker networks allow containers to communicate with each other. Docker networks configure communications between neighboring containers and external services. Containers must be connected to a Docker network to receive network connectivity. By default, all containers launched with Docker are attached to the `bridge` network unless otherwise specified.

For applications like PHP + MySQL, Docker networking makes it easy for services to find and talk to each other by name.

***

### Docker Network Types

- **bridge**
  Bridge networks create a software-based bridge between your host and the container. Containers connected to the network can communicate with each other, but they’re isolated from those outside the network.

- **host**
  Containers that use the host network mode share your host’s network stack without any isolation. They aren’t allocated their own IP addresses, and port binds will be published directly to your host’s network interface. This means a container process that listens on port 80 will bind to `<your_host_ip>:80`.

- **none**
  The none network type in Docker disables all networking for a container. It prevents the container from being connected to any external network, including the default bridge network.

- **overlay**
  Overlay networks are distributed networks that span multiple Docker hosts. The network allows all the containers running on any of the hosts to communicate with each other without requiring OS-level routing support.

- **ipvlan**
  IPvLAN is an advanced driver that offers precise control over the IPv4 and IPv6 addresses assigned to your containers, as well as layer 2 and 3 VLAN tagging and routing.

- **macvlan**
  macvlan is another advanced option that allows containers to appear as physical devices on your network. It works by assigning each container in the network a unique MAC address.

***

### Why Use a Custom Network?

- Containers can communicate via their service name (not IP).
- DNS is automatically handled by Docker.
- Better isolation and control than default `bridge`.

***

### Docker Network Types Comparison

| Network Type | Best Use Case |
|--------------|---------------|
| `bridge`     | Default option. Suitable for most cases. Containers can talk via IP/DNS and access LAN/Internet. |
| `host`       | Use when you need containers to bind directly to host’s ports, sacrificing isolation. |
| `overlay`    | Required for communication across multiple Docker hosts (e.g. in Swarm). |
| `macvlan`    | Makes containers look like physical devices; ideal for traffic monitoring apps. |
| `ipvlan`     | Advanced usage with control over IPs, tags, and routing in dense environments. |
| `none`       | Disables networking completely. Great for isolating services for security reasons. |

***

***

### How Docker Networking Works

Docker uses your host’s network stack along with Linux kernel features to provide isolated and flexible container networking.

- Docker manipulates `iptables` rules behind the scenes to route traffic to and from containers.
- These rules ensure that traffic entering the host is properly forwarded to the correct container.
- Each container runs inside its own **network namespace**, isolating its networking stack from the host and other containers.
- Virtual network interfaces (veth pairs) are created on the host and connected to each container to allow communication.
- Docker automatically configures these low-level details for you, so you don’t need to manage `iptables` manually.

Despite being built on complex networking primitives, Docker abstracts away the internals to give you a simple and predictable container networking experience.

***

***

### Docker Networking vs. VM Networking

| Aspect               | Docker Networking                                                                 | VM Networking                                                               |
|----------------------|------------------------------------------------------------------------------------|-----------------------------------------------------------------------------|
| **Isolation Mechanism** | Uses namespaces and `iptables` for network isolation                             | Each VM has its own full networking stack, offering stronger isolation     |
| **Terminology**         | Docker’s `bridge` network ≈ NAT-based network in VMs (similar function, different name) | Virtual networks often use more standardized terms                          |
| **Network Flexibility** | Opinionated and simplified, but can be extended with `macvlan` or plugins         | VMs support more complex topologies by default                              |
| **Customization**       | Supports integration with physical networks (`macvlan`, third-party plugins)       | Highly customizable via hypervisor-level tools                             |
| **Use Case Efficiency** | Lightweight, optimized for container-to-container communication                   | Better suited for full app stacks with stricter boundaries                  |

> Docker is ideal for lightweight, fast-moving containerized services.  
> VMs are better for full system isolation or legacy workloads requiring full OS-level separation.

***
### Example: PHP App Connecting from NGINX via Docker Network

**Project structure:**

```
php-nginx-network/
├── php/
│   ├── Dockerfile
│   └── index.php
├── nginx/
│   ├── default.conf
│   └── ping.txt
```

**php/index\.php**

```php
<?php
// Serve own ping response
if ($_SERVER['REQUEST_URI'] === '/ping') {
    header('Content-Type: text/plain');
    echo "pong from PHP";
    exit;
}

// Call nginx service
$nginxResponse = @file_get_contents('http://nginx/ping');

echo "<h1>PHP ↔ NGINX via Docker Network</h1>";
echo "<p><strong>PHP says:</strong> pong from PHP</p>";
echo "<p><strong>NGINX says:</strong> " . htmlspecialchars($nginxResponse ?: 'no response') . "</p>";
```

**php/Dockerfile**

```Dockerfile
FROM php:8.2-cli

WORKDIR /app
COPY . .
CMD ["php", "-S", "0.0.0.0:80", "index.php"]
```

***

**nginx/default.conf**

```nginx
server {
    listen 80;

    location /ping {
        default_type text/plain;
        alias /usr/share/nginx/html/ping.txt;
    }
}
```

***

**nginx/ping.txt**

```txt
pong from NGINX
```

***

### Create and Use a Custom Docker Network

1. **Create the network**

```bash
docker network create appnet
```

2. **Build PHP and run image**

```bash
docker build -t simple-app-php-with-network-api ./php
docker run -d --name php-api \
  --network appnet \
  -p 8080:80 \
  simple-app-php-with-network-api
```

3. **Run nginx container**

```bash
docker run -d --name nginx \
  --network appnet \
  -v $(pwd)/nginx/default.conf:/etc/nginx/conf.d/default.conf \
  -v $(pwd)/nginx/ping.txt:/usr/share/nginx/html/ping.txt \
  nginx:alpine
```

The PHP app connects to the database using the hostname `mysql` because both containers are in the same `appnet` network.

***

### Useful Docker Networking Commands

- **List Docker networks**
  ```bash
  docker network ls
  ```

- **Inspect a network**
  ```bash
  docker network inspect appnet
  ```

- **Connect a running container to a network**
  ```bash
  docker network connect appnet some_container
  ```

- **Disconnect a container**
  ```bash
  docker network disconnect appnet some_container
  ```

- **Remove a user-defined network**
  ```bash
  docker network rm appnet
  ```

  > ⚠️ Docker will not allow removal of a network that still has active containers attached.


---

## Docker Compose

### What is Docker Compose?

Docker Compose is a tool that makes it easier to define and manage multi-container Docker applications. It simplifies running interconnected services, such as a frontend, backend API, and database, by allowing them to be launched and controlled together.

Using a YAML configuration file (typically `docker-compose.yml`), you can describe each service and its dependencies as code. This setup can be committed to your source repository for consistent deployments. Once defined, all services can be started with a single `docker compose` command, making it easier to coordinate development or testing environments.

### Why Use Docker Compose?

Compose simplifies managing multi-container apps by allowing you to define everything—like services, volumes, and ports—in one config file. With `docker compose up`, you can launch the entire stack (e.g., web app + database + cache) in one go, ensuring:

- Consistent deployments across environments  
- Easy reuse of your setup  
- Fewer mistakes from manual configuration  
- Better developer experience  


***

### Benefits of Docker Compose

- **Centralized config**: One YAML file defines everything.
- **Service discovery**: Services can reach each other by name.
- **Automatic network**: A default user-defined bridge network is created.
- **Isolation**: Each project runs in its own network and containers.

***

### Difference Between Docker and Docker Compose

#### What’s the Difference?

- **Docker** lets you build and run one container at a time — it’s great for packaging a single app with its dependencies.
- **Docker Compose** helps you manage and orchestrate multiple containers together (like a full app stack) using a `docker-compose.yml` file.

#### Comparison Table

| Feature         | Docker                              | Docker Compose                              |
|-----------------|-------------------------------------|---------------------------------------------|
| **Scope**       | Single containers                   | Multi-container stacks                      |
| **Tooling**     | Docker CLI (`docker run`, `start`)  | YAML config + CLI (`docker compose`)        |
| **Relationships** | Manually linked                  | Automatically coordinated                   |
| **Use Case**    | Isolated services                   | Full systems like web + DB + cache          |
| **Deployment**  | One container at a time             | Whole stack with one command                |


***

### Benefits of Docker Compose

- **Simplified Multi-Container Applications**
  Docker Compose allows you to define and manage multi-container Docker applications using a single YAML file.

- **Ease of Use**
  With a single command, you can start, stop, and rebuild all the services defined in a `docker-compose.yml` file.

- **Networking**
  Docker Compose sets up a network for your application’s services, enabling them to communicate with each other using service names.

- **Volume Management**
  It allows for easy management of data volumes, ensuring persistent data across container restarts and clean separation of data from containers.

***

### Example: PHP + MySQL Stack With phpMyAdmin

**Project structure:**

```
simple-php-app-compose/
├── docker-compose.yml
├── index.php
├── Dockerfile
```

**index\.php**

```php
<?php
$conn = new mysqli("mysql", "root", "rootpass", "appdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected to MySQL successfully!";
?>
```

**Dockerfile**

```Dockerfile
FROM php:8.2-apache
WORKDIR /var/www/html
COPY . .
RUN docker-php-ext-install mysqli
EXPOSE 80
```

**docker-compose.yml**

```yaml
version: "3.9"
services:
  web:
    build:
      context: .
    ports:
      - "8080:80"
    volumes:
      - ./logs:/var/www/html/logs
    depends_on:
      - mysql

  mysql:
    image: mysql:8
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: appdb
    volumes:
      - mysql_data:/var/lib/mysql

  phpmyadmin:
    image: phpmyadmin
    ports:
      - "8081:80"
    environment:
      PMA_HOST: mysql
      PMA_PORT: 3306

volumes:
  mysql_data:
```

***

### Compose Concepts Explained

- `build:`
  Specifies how to build the container image from a `Dockerfile`. Usually points to the current directory (`.`), but can also be a remote Git repo or subfolder.

- `volumes:`
  Mounts host directories or Docker-managed volumes into containers. Useful for persisting data or enabling live code reloading during development.

- `depends_on:`
  Declares dependency order between services. It ensures a container (e.g., `mysql`) starts before another (e.g., `web`), but **does not** guarantee readiness — you may still need health checks or wait scripts.

- `environment:`
  Sets environment variables inside the container. These can configure services like MySQL credentials, app settings, or any config expected by your app.

- `ports:`
  Maps container ports to host machine ports. For example, `"8080:80"` exposes port `80` inside the container as `localhost:8080` on your machine.

***

### Useful Docker Compose Commands

- Start all services in the background:

  ```bash
  docker-compose up -d
  ```

- Stop and remove all containers:

  ```bash
  docker-compose down
  ```

- View real-time logs from all services:

  ```bash
  docker-compose logs -f
  ```

- List running containers in the project:

  ```bash
  docker-compose ps
  ```

- Rebuild and restart containers:

  ```bash
  docker-compose up -d --build
  ```

***

### Best Practices

- Use `.env` files to store credentials and configs.
- Keep services modular and lightweight.
- Mount logs and app data with volumes for easier backup.
- Avoid hardcoded secrets — consider Docker secrets or external configs.

***

### Summary

Docker Compose is essential for local development of multi-container applications. It abstracts networking, volumes, and service setup into a simple file — making your projects easy to share, repeat, and scale.

---

## Docker Commands

### Run a container from an image

```bash
docker run <image_name>
```

- Creates and starts a container based on the image.

**Example:**

```bash
docker run nginx
```

---

### Run a container with a custom command

```bash
docker run <image_name> <command>
```

**Example:**

```bash
docker run ubuntu ls
```

---

### List running containers

```bash
docker ps
```

**To list all (including stopped):**

```bash
docker ps -a
```

---

### Create a container without starting it

```bash
docker create <image_name>
```

---

### Start a stopped container

```bash
docker start <container_id>
```

---

### Remove unused resources

```bash
docker system prune
```

**Aggressive cleanup:**

```bash
docker system prune -a
```

---

### View logs of a container

```bash
docker logs <container_name>
```

**Follow logs in real-time:**

```bash
docker logs -f <container_name>
```

---

### Docker Compose: View container status

```bash
docker-compose ps
```

---

### Docker Compose: Stop and clean up

```bash
docker-compose down -v
```

---

## Full Example: PHP CRUD App with Docker (Volume, Network, Compose)

This is a basic CRUD app written in raw PHP (no framework) that demonstrates how Docker volumes, networks, Dockerfile, and Compose work together.

---

### Folder Structure

```
php-crud-docker/
├── docker-compose.yaml
├── web/
│   ├── Dockerfile
│   └── index.php
└── db/
    └── init.sql
```

---

### docker-compose.yaml

```yaml
version: '3.8'

services:
  web:
    build: ./web
    ports:
      - "8080:80"
    volumes:
      - ./web:/var/www/html
    networks:
      - app-net
    depends_on:
      - db

  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: myapp
      MYSQL_USER: user
      MYSQL_PASSWORD: pass
    volumes:
      - db-data:/var/lib/mysql
      - ./db/init.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - app-net

volumes:
  db-data:

networks:
  app-net:
```

---

### web/Dockerfile

```Dockerfile
FROM php:7.4-apache

# Enable PDO and MySQL extensions
RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/
```

---

### web/index.php

```php
<?php
// Connect to MySQL
$pdo = new PDO('mysql:host=db;dbname=myapp', 'user', 'pass', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $stmt = $pdo->prepare("INSERT INTO users (name) VALUES (:name)");
    $stmt->execute(['name' => $_POST['name']]);
    header("Location: /");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: /");
    exit;
}

// Fetch all users
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP Docker CRUD</title>
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        input[type="text"] { padding: 0.5rem; width: 200px; }
        button { padding: 0.5rem; }
        ul { list-style: none; padding: 0; }
        li { margin: 0.5rem 0; }
    </style>
</head>
<body>
    <h1>Simple PHP CRUD</h1>

    <h2>Add User</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="Enter name" required>
        <button type="submit">Add</button>
    </form>

    <h2>Users</h2>
    <ul>
        <?php foreach ($users as $user): ?>
            <li>
                <?= htmlspecialchars($user['name']) ?>
                <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Are you sure?')">❌</a>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
```

---

### db/init.sql

```sql
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);
```

---

### Running the App

```bash
docker compose up --build
```

- Visit: [http://localhost:8080](http://localhost:8080)
- Add, view, and delete users.

---

### Docker Concepts Demonstrated

| Feature            | Where It’s Used                               |
|--------------------|-----------------------------------------------|
| **Dockerfile**     | Builds custom PHP+Apache container in `/web`  |
| **Docker Compose** | Orchestrates multi-service setup               |
| **Volumes**        | Used for persisting MySQL data + code mount   |
| **Network**        | Services communicate via internal network name (`db-data`) |
| **Depends_on**     | Ensures DB container starts before PHP        |


---

## Summary

- Docker helps build reproducible dev environments.
- Volumes and networks are critical for real-world apps.
- Docker Compose simplifies multi-container projects.
- The PHP example shows how Docker can streamline local dev.

***

## Troubleshooting Tips

### Docker: Permission Denied on Port 80

```
[emerg] bind() to 0.0.0.0:80 failed (13: Permission denied)
```

**Solution:** Use a port above 1024 (e.g. 8080), or run with elevated permissions:

```bash
sudo docker run -p 80:80 your-image
```

---

### Docker Volume Not Mounted Properly on Windows

**Issue:** Path errors like `error: no such file or directory`.

**Solution:** Use full absolute paths, and ensure Docker Desktop is allowed to access the shared drive.

```bash
docker run -v /c/Users/YourUser/project:/app your-image
```

Also check Docker Desktop > Settings > Resources > File Sharing.

---

### MySQL Container Starts Too Late in Compose

Even though you use `depends_on`, MySQL may not be ready in time.

**Solution:** Use retry logic in your PHP code or wait-for-it/wait-for scripts in entrypoint.

---

### Logs Are Not Appearing in Mounted Volume

**Issue:** App logs don't show up in the mounted `logs/` folder.

**Solution:** Ensure:
- The folder exists in your codebase.
- You’ve granted the correct write permissions inside Dockerfile.
- You use `www-data` or equivalent user for web processes.

```Dockerfile
RUN chown -R www-data:www-data /var/www/html/logs
```

---