// Initialize Three.js Scene
const scene = new THREE.Scene();
const container = document.getElementById('canvas-container');

// Create Camera
const camera = new THREE.PerspectiveCamera(
    75, // Field of view
    window.innerWidth / window.innerHeight, // Aspect ratio
    0.1, // Near clipping plane
    1000 // Far clipping plane
);
camera.position.z = 5;

// Create Renderer
const renderer = new THREE.WebGLRenderer({ 
    antialias: true,
    alpha: true 
});
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(window.devicePixelRatio);
renderer.setClearColor(0x000000, 0);
container.appendChild(renderer.domElement);

// Add Lighting
const ambientLight = new THREE.AmbientLight(0x404040, 2);
scene.add(ambientLight);

const pointLight = new THREE.PointLight(0x00ff88, 2, 100);
pointLight.position.set(10, 10, 10);
scene.add(pointLight);

const pointLight2 = new THREE.PointLight(0xff0066, 2, 100);
pointLight2.position.set(-10, -10, -10);
scene.add(pointLight2);

// Create Multiple 3D Objects
const objects = [];

// 1. Main Torus Knot
const geometry1 = new THREE.TorusKnotGeometry(1, 0.3, 128, 16);
const material1 = new THREE.MeshPhongMaterial({ 
    color: 0x00ff88,
    shininess: 100,
    specular: 0xffffff,
    wireframe: false
});
const torusKnot = new THREE.Mesh(geometry1, material1);
scene.add(torusKnot);
objects.push(torusKnot);

// 2. Wireframe Sphere
const geometry2 = new THREE.SphereGeometry(1.5, 32, 32);
const material2 = new THREE.MeshBasicMaterial({ 
    color: 0xff0066,
    wireframe: true,
    transparent: true,
    opacity: 0.3
});
const wireframeSphere = new THREE.Mesh(geometry2, material2);
scene.add(wireframeSphere);
objects.push(wireframeSphere);

// 3. Small orbiting cubes
for (let i = 0; i < 5; i++) {
    const geometry3 = new THREE.BoxGeometry(0.2, 0.2, 0.2);
    const material3 = new THREE.MeshPhongMaterial({ 
        color: 0x4488ff,
        shininess: 100,
        emissive: 0x2244aa
    });
    const cube = new THREE.Mesh(geometry3, material3);
    cube.position.x = Math.cos(i * Math.PI * 2 / 5) * 2;
    cube.position.z = Math.sin(i * Math.PI * 2 / 5) * 2;
    scene.add(cube);
    objects.push(cube);
}

// Add Particles
const particlesGeometry = new THREE.BufferGeometry();
const particlesCount = 1000;
const posArray = new Float32Array(particlesCount * 3);

for (let i = 0; i < particlesCount * 3; i++) {
    posArray[i] = (Math.random() - 0.5) * 10;
}

particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
const particlesMaterial = new THREE.PointsMaterial({
    size: 0.005,
    color: 0x00ff88,
    transparent: true,
    blending: THREE.AdditiveBlending
});
const particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
scene.add(particlesMesh);

// Mouse Interaction
let mouseX = 0;
let mouseY = 0;
let targetX = 0;
let targetY = 0;

document.addEventListener('mousemove', (event) => {
    mouseX = (event.clientX / window.innerWidth) * 2 - 1;
    mouseY = -(event.clientY / window.innerHeight) * 2 + 1;
});

// Scroll interaction
window.addEventListener('scroll', () => {
    const scrollY = window.scrollY;
    camera.position.z = 5 + scrollY * 0.001;
});

// Animation Loop
function animate() {
    requestAnimationFrame(animate);

    // Smooth mouse following
    targetX += (mouseX * 0.5 - targetX) * 0.05;
    targetY += (mouseY * 0.5 - targetY) * 0.05;

    // Rotate objects
    torusKnot.rotation.x += 0.005;
    torusKnot.rotation.y += 0.005;
    
    wireframeSphere.rotation.x -= 0.003;
    wireframeSphere.rotation.y -= 0.003;

    // Rotate small cubes around the center
    objects.forEach((obj, index) => {
        if (index >= 2) { // Skip torus knot and wireframe sphere
            obj.rotation.x += 0.02;
            obj.rotation.y += 0.02;
            
            const angle = Date.now() * 0.001 + index;
            obj.position.x = Math.cos(angle) * 2;
            obj.position.z = Math.sin(angle) * 2;
        }
    });

    // Rotate particles
    particlesMesh.rotation.y += 0.0002;
    particlesMesh.rotation.x += 0.0001;

    // Move camera based on mouse
    camera.position.x += (targetX - camera.position.x) * 0.05;
    camera.position.y += (targetY - camera.position.y) * 0.05;
    camera.lookAt(scene.position);

    // Change colors dynamically
    const time = Date.now() * 0.001;
    material1.color.setHSL(Math.sin(time * 0.5) * 0.5 + 0.5, 0.8, 0.5);
    
    // Update lights
    pointLight.intensity = 1 + Math.sin(time) * 0.5;
    pointLight2.intensity = 1 + Math.cos(time) * 0.5;

    renderer.render(scene, camera);
}

// Handle Window Resize
window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
});

// Start Animation
animate();

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        target.scrollIntoView({
            behavior: 'smooth'
        });
    });
});

console.log('3D Website Loaded Successfully! 🚀');
