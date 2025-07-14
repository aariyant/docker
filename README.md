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
  docker volume create flasklogs
  docker run -v flasklogs:/app/logs simple-flask-app
  ```

- **Bind Mounts**  
  Maps a specific directory on your host to the container. Ideal for local development to sync your code.

  ```bash
  docker run -v $(pwd):/app simple-flask-app
  ```

- **tmpfs Mounts**  
  Stores data only in memory. Useful for temporary files or sensitive information.

  ```bash
  docker run --tmpfs /app/tmp simple-flask-app
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

### Example: Add Log Volume to Flask App

Let’s modify the **Dockerfile** to write logs into a folder:

**app.py**

```python
from flask import Flask
import logging

app = Flask(__name__)

logging.basicConfig(filename="logs/app.log", level=logging.INFO)

@app.route("/")
def hello():
    app.logger.info("Home route accessed")
    return "Hello from Flask inside Docker!"

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
```

**Dockerfile (unchanged)**

```Dockerfile
FROM python:3.11-slim
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY . .
EXPOSE 5000
CMD ["python", "app.py"]
```

**Run with named volume for logs:**

```bash
docker volume create flasklogs
docker run -p 5000:5000 -v flasklogs:/app/logs simple-flask-app
```

**Run with bind mount for live editing:**

```bash
docker run -p 5000:5000 -v $(pwd):/app simple-flask-app
```

Logs will be written into the mounted folder (named volume or bind path), and persist across restarts.

***

### Manage Data with Docker Volumes

Volumes let you persist data created by containers or share data between them. They are the preferred mechanism for managing data in Docker because they are designed to be independent of the container lifecycle.

***

### Types of Docker Volumes

Docker supports multiple volume types. Here's a breakdown:

- **Named Volumes**  
  Managed by Docker and stored under `/var/lib/docker/volumes/`. These are great for persistent data like databases or application files.

  ```bash
  docker volume create phplogs
  docker run -v phplogs:/var/www/html/logs simple-php-app
  ```

- **Bind Mounts**  
  Maps a specific directory on your host to the container. Ideal for local development to sync your code.

  ```bash
  docker run -v $(pwd):/var/www/html simple-php-app
  ```

- **tmpfs Mounts**  
  Stores data only in memory. Useful for temporary files or sensitive information.

  ```bash
  docker run --tmpfs /var/www/html/tmp simple-php-app
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
- Application data  
- Essential caches  
- Convenient data backups  
- Share data between containers  
- Write to remote filesystems

***

### Example: Add Log Volume to PHP App

**index.php**

```php
<?php
$logPath = "logs/access.log";
file_put_contents($logPath, "[" . date("Y-m-d H:i:s") . "] Accessed\n", FILE_APPEND);
echo "Hello from PHP inside Docker!";
?>
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
docker run -p 8080:80 -v phplogs:/var/www/html/logs simple-php-app
```

**Run with bind mount for live editing:**

```bash
docker run -p 8080:80 -v $(pwd):/var/www/html simple-php-app
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

> 📝 Docker is ideal for lightweight, fast-moving containerized services.  
> 🧱 VMs are better for full system isolation or legacy workloads requiring full OS-level separation.

***
### Example: PHP App Connecting to MySQL via Docker Network

Project structure:

```
simple-php-app-with-mysql/
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

***

### Create and Use a Custom Docker Network

1. **Create the network**

```bash
docker network create appnet
```

2. **Run MySQL container in that network**

```bash
docker run -d \
  --name mysql \
  --network appnet \
  -e MYSQL_ROOT_PASSWORD=rootpass \
  -e MYSQL_DATABASE=appdb \
  mysql:8
```

3. **Build and run the PHP container in the same network**

```bash
docker build -t simple-php-app .
docker run -d \
  --name php \
  --network appnet \
  -p 8080:80 \
  simple-php-app
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

Docker Compose simplifies running multi-container applications by letting you define services, volumes, networks, and environment settings in a single YAML file (`docker-compose.yml`).

Instead of running multiple `docker run` commands manually, Compose lets you start everything with:

```bash
docker-compose up -d
```

***

### Benefits of Docker Compose

- **Centralized config**: One YAML file defines everything.
- **Service discovery**: Services can reach each other by name.
- **Automatic network**: A default user-defined bridge network is created.
- **Isolation**: Each project runs in its own network and containers.

***

### Example: PHP + MySQL Stack With phpMyAdmin

**Project structure:**

```
simple-php-app/
├── docker-compose.yml
├── index.php
├── Dockerfile
```

**index.php**

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
    build: .
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

- `build:` points to a Dockerfile in the current directory.
- `volumes:` maps host paths or named volumes into containers.
- `depends_on:` ensures MySQL starts before the web container (but doesn’t wait for it to be _ready_).
- `environment:` sets container environment variables (used by MySQL or phpMyAdmin).
- `ports:` maps container port to a host port.

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

## Docker Use Case with PHP Project

### Goal
Run a full-stack PHP application with MySQL and phpMyAdmin using Docker.

### Folder Structure

```
my-php-project/
├── docker-compose.yml
├── Dockerfile
├── index.php
```

### Example Files

**index.php**

```php
<?php
$mysqli = new mysqli("db", "root", "root", "appdb");
echo "MySQL connection status: " . ($mysqli->connect_errno ? "Failed" : "Successful");
?>
```

**Dockerfile**

```Dockerfile
FROM php:8.2-apache
COPY . /var/www/html
RUN docker-php-ext-install mysqli
```

**docker-compose.yml**

```yaml
version: "3.8"
services:
  web:
    build: .
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
  db:
    image: mysql:8
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: appdb
  phpmyadmin:
    image: phpmyadmin
    ports:
      - "8081:80"
    environment:
      PMA_HOST: db
```

### How to Run

```bash
docker-compose up -d
```

- Open http://localhost:8080 to see your PHP app.
- Open http://localhost:8081 for phpMyAdmin UI.
- Your app will connect to the MySQL container via Docker network.

---

## Summary

- Docker helps build reproducible dev environments.
- Volumes and networks are critical for real-world apps.
- Docker Compose simplifies multi-container projects.
- The PHP example shows how Docker can streamline local dev.
