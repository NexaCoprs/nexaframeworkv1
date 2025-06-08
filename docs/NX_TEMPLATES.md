# 🎨 Guide Complet des Templates .nx

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/nexa-framework/nexa)
[![Documentation](https://img.shields.io/badge/docs-latest-blue.svg)](https://docs.nexa-framework.com)
[![VSCode Extension](https://img.shields.io/badge/vscode-extension-blue.svg)](https://marketplace.visualstudio.com/items?itemName=nexa.nx-template-support)

Les templates `.nx` sont le cœur moderne de Nexa Framework, offrant une syntaxe intuitive, une réactivité native et une auto-découverte complète des composants.

## 📋 Table des Matières

- [🚀 Introduction](#-introduction)
- [🎯 Syntaxe de Base](#-syntaxe-de-base)
- [🧩 Composants](#-composants)
- [🔄 Réactivité](#-réactivité)
- [📡 Directives](#-directives)
- [🎨 Styling](#-styling)
- [⚡ JavaScript](#-javascript)
- [🔧 Configuration](#-configuration)
- [📚 Exemples Avancés](#-exemples-avancés)
- [🛠️ Outils de Développement](#️-outils-de-développement)

---

## 🚀 Introduction

### Qu'est-ce qu'un Template .nx ?

Les templates `.nx` sont des fichiers de composants modernes qui combinent :
- **HTML sémantique** avec des composants Nexa
- **CSS moderne** avec support des variables et animations
- **JavaScript réactif** avec liaison de données bidirectionnelle
- **Directives intelligentes** pour la logique métier

### Avantages par rapport à Blade

| Fonctionnalité | Templates .nx | Blade Laravel |
|---|---|---|
| **Réactivité** | ✅ Native | ❌ Aucune |
| **Composants** | ✅ Auto-découverte | ❌ Manuel |
| **Validation** | ✅ Temps réel | ❌ Serveur uniquement |
| **Performance** | ✅ Optimisations avancées | ❌ Standard |
| **IntelliSense** | ✅ Support VSCode complet | ❌ Limité |
| **Hot Reload** | ✅ Instantané | ❌ Rechargement complet |

---

## 🎯 Syntaxe de Base

### Structure d'un Fichier .nx

```nx
<!-- Directives Nexa -->
@entity('User')
@handler('UserProfileHandler')
@auth('required')

<!-- Template HTML avec composants Nexa -->
<nx:card :title="{{ user.name }}">
    <nx:icon :name="user.avatar" :size="64" />
    
    <div class="profile-info">
        <h2>{{ user.name }}</h2>
        <p>{{ user.email }}</p>
        
        @if(user.isOnline)
            <nx:badge :variant="success">En ligne</nx:badge>
        @else
            <nx:badge :variant="secondary">Hors ligne</nx:badge>
        @endif
    </div>
</nx:card>

<!-- Styles CSS -->
<style>
.profile-info {
    padding: 1rem;
    text-align: center;
}
</style>

<!-- Logique JavaScript -->
<script>
export default {
    data() {
        return {
            user: {
                name: 'John Doe',
                email: 'john@example.com',
                avatar: 'user-circle',
                isOnline: true
            }
        };
    }
};
</script>
```

### Interpolation de Variables

```nx
<!-- Interpolation simple -->
<h1>{{ title }}</h1>

<!-- Expressions -->
<p>{{ user.firstName + ' ' + user.lastName }}</p>

<!-- Fonctions -->
<span>{{ formatDate(user.createdAt) }}</span>

<!-- Conditions inline -->
<div>{{ user.isActive ? 'Actif' : 'Inactif' }}</div>
```

---

## 🧩 Composants

### Composants Natifs Nexa

#### Navigation
```nx
<nx:navigation 
    :brand="'Mon App'"
    :items="navigationItems"
    :user="currentUser"
    @logout="handleLogout">
</nx:navigation>
```

#### Cartes
```nx
<nx:card 
    :title="'Profil Utilisateur'"
    :subtitle="'Informations personnelles'"
    :image="user.avatar"
    :actions="cardActions">
    
    <p>{{ user.bio }}</p>
    
    <template #footer>
        <nx:button @click="editProfile">Modifier</nx:button>
    </template>
</nx:card>
```

#### Formulaires
```nx
<nx:form 
    :model="userForm"
    :validation="userValidation"
    @submit="saveUser">
    
    <nx:input 
        :label="'Nom'"
        v-model="userForm.name"
        :required="true" />
    
    <nx:input 
        :label="'Email'"
        :type="'email'"
        v-model="userForm.email"
        :required="true" />
    
    <nx:button :type="'submit'">Enregistrer</nx:button>
</nx:form>
```

#### Modales
```nx
<nx:modal 
    :show="showModal"
    :title="'Confirmation'"
    @close="showModal = false">
    
    <p>Êtes-vous sûr de vouloir supprimer cet élément ?</p>
    
    <template #footer>
        <nx:button @click="confirmDelete" :variant="'danger'">
            Supprimer
        </nx:button>
        <nx:button @click="showModal = false">
            Annuler
        </nx:button>
    </template>
</nx:modal>
```

### Composants Personnalisés

#### Création d'un Composant
```nx
<!-- components/UserCard.nx -->
@component('UserCard')
@props(['user', 'showActions'])

<div class="user-card">
    <nx:avatar :src="user.avatar" :size="'large'" />
    
    <div class="user-info">
        <h3>{{ user.name }}</h3>
        <p>{{ user.role }}</p>
        
        @if(showActions)
            <div class="actions">
                <nx:button @click="$emit('edit', user)">
                    Modifier
                </nx:button>
                <nx:button @click="$emit('delete', user)" :variant="'danger'">
                    Supprimer
                </nx:button>
            </div>
        @endif
    </div>
</div>

<style scoped>
.user-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.user-info {
    margin-left: 1rem;
    flex: 1;
}

.actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}
</style>
```

#### Utilisation du Composant
```nx
<!-- Auto-découverte du composant -->
<UserCard 
    :user="selectedUser"
    :show-actions="true"
    @edit="editUser"
    @delete="deleteUser" />
```

---

## 🔄 Réactivité

### Liaison de Données Bidirectionnelle

```nx
<script>
export default {
    data() {
        return {
            message: 'Hello World',
            count: 0,
            user: {
                name: '',
                email: ''
            }
        };
    },
    
    computed: {
        reversedMessage() {
            return this.message.split('').reverse().join('');
        },
        
        isValidUser() {
            return this.user.name && this.user.email;
        }
    },
    
    watch: {
        count(newVal, oldVal) {
            console.log(`Count changed from ${oldVal} to ${newVal}`);
        }
    },
    
    methods: {
        increment() {
            this.count++;
        },
        
        resetForm() {
            this.user = { name: '', email: '' };
        }
    }
};
</script>

<template>
    <div>
        <!-- Liaison bidirectionnelle -->
        <nx:input v-model="message" :label="'Message'" />
        <p>Message inversé : {{ reversedMessage }}</p>
        
        <!-- Événements -->
        <nx:button @click="increment">Count: {{ count }}</nx:button>
        
        <!-- Formulaire réactif -->
        <nx:form>
            <nx:input v-model="user.name" :label="'Nom'" />
            <nx:input v-model="user.email" :label="'Email'" />
            
            <nx:button 
                :disabled="!isValidUser"
                @click="saveUser">
                Enregistrer
            </nx:button>
        </nx:form>
    </div>
</template>
```

### Réactivité Avancée

```nx
<script>
import { ref, reactive, computed, watch } from 'vue';

export default {
    setup() {
        // Références réactives
        const count = ref(0);
        const message = ref('Hello');
        
        // Objets réactifs
        const state = reactive({
            user: {
                name: 'John',
                age: 30
            },
            todos: []
        });
        
        // Propriétés calculées
        const doubleCount = computed(() => count.value * 2);
        const userInfo = computed(() => 
            `${state.user.name} (${state.user.age} ans)`
        );
        
        // Watchers
        watch(count, (newVal) => {
            console.log('Count changed:', newVal);
        });
        
        // Méthodes
        const increment = () => count.value++;
        const addTodo = (text) => {
            state.todos.push({
                id: Date.now(),
                text,
                completed: false
            });
        };
        
        return {
            count,
            message,
            state,
            doubleCount,
            userInfo,
            increment,
            addTodo
        };
    }
};
</script>
```

---

## 📡 Directives

### Directives de Structure

#### @if / @else / @elseif
```nx
@if(user.isAdmin)
    <nx:admin-panel />
@elseif(user.isModerator)
    <nx:moderator-panel />
@else
    <nx:user-panel />
@endif
```

#### @for / @foreach
```nx
<!-- Boucle simple -->
@for(i = 0; i < 10; i++)
    <div>Item {{ i }}</div>
@endfor

<!-- Boucle sur collection -->
@foreach(users as user)
    <UserCard :user="user" :key="user.id" />
@endforeach

<!-- Avec index -->
@foreach(items as item, index)
    <div>{{ index }}: {{ item.name }}</div>
@endforeach
```

#### @switch / @case
```nx
@switch(user.role)
    @case('admin')
        <nx:admin-dashboard />
        @break
    
    @case('moderator')
        <nx:moderator-dashboard />
        @break
    
    @default
        <nx:user-dashboard />
@endswitch
```

### Directives Métier

#### @entity
```nx
@entity('User')
@entity('Post', { with: ['comments', 'author'] })
@entity('Product', { cache: true, ttl: 3600 })
```

#### @handler
```nx
@handler('UserController')
@handler('PostController', { middleware: ['auth', 'throttle'] })
@handler('ApiController', { prefix: 'api/v1' })
```

#### @auth
```nx
@auth('required')
@auth('guest')
@auth('role:admin')
@auth('permission:edit-posts')
```

#### @cache
```nx
@cache(3600)
@cache('user-profile', 1800)
@cache({ key: 'posts', ttl: 3600, tags: ['posts'] })
```

#### @validate
```nx
@validate({
    name: 'required|string|max:255',
    email: 'required|email|unique:users',
    age: 'required|integer|min:18'
})
```

---

## 🎨 Styling

### CSS Scoped

```nx
<template>
    <div class="component">
        <h1 class="title">Mon Composant</h1>
        <p class="description">Description du composant</p>
    </div>
</template>

<style scoped>
.component {
    padding: 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 1rem;
    color: white;
}

.title {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.description {
    font-size: 1.1rem;
    opacity: 0.9;
    line-height: 1.6;
}
</style>
```

### Variables CSS

```nx
<style>
:root {
    --primary-color: #3b82f6;
    --secondary-color: #64748b;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
    
    --font-family: 'Inter', sans-serif;
    --border-radius: 0.5rem;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.button {
    background-color: var(--primary-color);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    font-family: var(--font-family);
}
</style>
```

### Animations

```nx
<template>
    <div class="animated-card" :class="{ 'is-visible': isVisible }">
        <h2>Carte Animée</h2>
        <p>Contenu avec animation</p>
    </div>
</template>

<style scoped>
.animated-card {
    transform: translateY(50px);
    opacity: 0;
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.animated-card.is-visible {
    transform: translateY(0);
    opacity: 1;
}

.animated-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}
</style>
```

---

## ⚡ JavaScript

### Lifecycle Hooks

```nx
<script>
export default {
    // Avant la création du composant
    beforeCreate() {
        console.log('beforeCreate');
    },
    
    // Après la création du composant
    created() {
        console.log('created');
        this.fetchData();
    },
    
    // Avant le montage dans le DOM
    beforeMount() {
        console.log('beforeMount');
    },
    
    // Après le montage dans le DOM
    mounted() {
        console.log('mounted');
        this.initializePlugins();
    },
    
    // Avant la mise à jour
    beforeUpdate() {
        console.log('beforeUpdate');
    },
    
    // Après la mise à jour
    updated() {
        console.log('updated');
    },
    
    // Avant la destruction
    beforeDestroy() {
        console.log('beforeDestroy');
        this.cleanup();
    },
    
    // Après la destruction
    destroyed() {
        console.log('destroyed');
    },
    
    methods: {
        fetchData() {
            // Récupération des données
        },
        
        initializePlugins() {
            // Initialisation des plugins
        },
        
        cleanup() {
            // Nettoyage des ressources
        }
    }
};
</script>
```

### Composition API

```nx
<script>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useStore } from 'vuex';

export default {
    setup() {
        const router = useRouter();
        const store = useStore();
        
        // État réactif
        const loading = ref(false);
        const error = ref(null);
        const data = reactive({
            users: [],
            currentPage: 1,
            totalPages: 0
        });
        
        // Propriétés calculées
        const hasUsers = computed(() => data.users.length > 0);
        const canLoadMore = computed(() => data.currentPage < data.totalPages);
        
        // Méthodes
        const fetchUsers = async (page = 1) => {
            loading.value = true;
            error.value = null;
            
            try {
                const response = await fetch(`/api/users?page=${page}`);
                const result = await response.json();
                
                data.users = result.data;
                data.currentPage = result.current_page;
                data.totalPages = result.last_page;
            } catch (err) {
                error.value = err.message;
            } finally {
                loading.value = false;
            }
        };
        
        const navigateToUser = (userId) => {
            router.push(`/users/${userId}`);
        };
        
        // Hooks de cycle de vie
        onMounted(() => {
            fetchUsers();
        });
        
        onUnmounted(() => {
            // Nettoyage si nécessaire
        });
        
        return {
            loading,
            error,
            data,
            hasUsers,
            canLoadMore,
            fetchUsers,
            navigateToUser
        };
    }
};
</script>
```

---

## 🔧 Configuration

### Configuration Globale

```javascript
// nexa.config.js
export default {
    templates: {
        // Répertoire des templates
        directory: 'interface',
        
        // Extensions de fichiers
        extensions: ['.nx'],
        
        // Auto-découverte des composants
        autoDiscovery: true,
        
        // Cache des templates
        cache: {
            enabled: true,
            ttl: 3600
        },
        
        // Optimisations
        optimization: {
            minify: true,
            treeshaking: true,
            lazyLoading: true
        }
    },
    
    components: {
        // Préfixe des composants
        prefix: 'nx',
        
        // Répertoires de composants
        directories: [
            'interface/components',
            'interface/layouts',
            'interface/pages'
        ],
        
        // Composants globaux
        global: [
            'nx:button',
            'nx:input',
            'nx:card'
        ]
    }
};
```

### Configuration VSCode

```json
{
    "files.associations": {
        "*.nx": "nx"
    },
    "emmet.includeLanguages": {
        "nx": "html"
    },
    "nx.validation.enabled": true,
    "nx.autoCompletion.enabled": true,
    "nx.preview.autoRefresh": true
}
```

---

## 📚 Exemples Avancés

### Dashboard Administrateur

```nx
@entity('User', { with: ['roles', 'permissions'] })
@handler('AdminController')
@auth('role:admin')
@cache('admin-dashboard', 1800)

<template>
    <div class="admin-dashboard">
        <nx:navigation 
            :brand="'Admin Panel'"
            :user="currentUser"
            @logout="logout" />
        
        <div class="dashboard-content">
            <div class="stats-grid">
                <nx:stat-card 
                    :title="'Utilisateurs'"
                    :value="stats.users"
                    :icon="'users'"
                    :trend="stats.usersTrend" />
                
                <nx:stat-card 
                    :title="'Revenus'"
                    :value="formatCurrency(stats.revenue)"
                    :icon="'dollar-sign'"
                    :trend="stats.revenueTrend" />
                
                <nx:stat-card 
                    :title="'Commandes'"
                    :value="stats.orders"
                    :icon="'shopping-cart'"
                    :trend="stats.ordersTrend" />
                
                <nx:stat-card 
                    :title="'Conversion'"
                    :value="stats.conversion + '%'"
                    :icon="'trending-up'"
                    :trend="stats.conversionTrend" />
            </div>
            
            <div class="charts-section">
                <nx:card :title="'Évolution des Ventes'">
                    <nx:chart 
                        :type="'line'"
                        :data="salesData"
                        :options="chartOptions" />
                </nx:card>
                
                <nx:card :title="'Répartition par Catégorie'">
                    <nx:chart 
                        :type="'doughnut'"
                        :data="categoryData" />
                </nx:card>
            </div>
            
            <div class="recent-activity">
                <nx:card :title="'Activité Récente'">
                    <nx:timeline>
                        @foreach(activities as activity)
                            <nx:timeline-item 
                                :time="activity.created_at"
                                :icon="activity.icon"
                                :color="activity.color">
                                {{ activity.description }}
                            </nx:timeline-item>
                        @endforeach
                    </nx:timeline>
                </nx:card>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { useStats } from '@/composables/useStats';

export default {
    setup() {
        const { currentUser, logout } = useAuth();
        const { stats, salesData, categoryData, activities, fetchStats } = useStats();
        
        const chartOptions = reactive({
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        });
        
        const formatCurrency = (value) => {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(value);
        };
        
        onMounted(() => {
            fetchStats();
        });
        
        return {
            currentUser,
            stats,
            salesData,
            categoryData,
            activities,
            chartOptions,
            formatCurrency,
            logout
        };
    }
};
</script>

<style scoped>
.admin-dashboard {
    min-height: 100vh;
    background-color: #f8fafc;
}

.dashboard-content {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.charts-section {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .charts-section {
        grid-template-columns: 1fr;
    }
}
</style>
```

### E-commerce Product Page

```nx
@entity('Product', { with: ['images', 'reviews', 'variants'] })
@handler('ProductController')
@cache('product-page', 3600)

<template>
    <div class="product-page">
        <div class="product-gallery">
            <nx:image-gallery 
                :images="product.images"
                :zoom="true"
                :thumbnails="true" />
        </div>
        
        <div class="product-info">
            <div class="product-header">
                <h1 class="product-title">{{ product.name }}</h1>
                <div class="product-rating">
                    <nx:rating 
                        :value="product.rating"
                        :readonly="true" />
                    <span class="review-count">
                        ({{ product.reviews_count }} avis)
                    </span>
                </div>
            </div>
            
            <div class="product-price">
                @if(product.sale_price)
                    <span class="original-price">{{ formatPrice(product.price) }}</span>
                    <span class="sale-price">{{ formatPrice(product.sale_price) }}</span>
                    <span class="discount">-{{ calculateDiscount() }}%</span>
                @else
                    <span class="current-price">{{ formatPrice(product.price) }}</span>
                @endif
            </div>
            
            <div class="product-variants">
                @if(product.variants.length > 0)
                    @foreach(product.variants as variant)
                        <div class="variant-group">
                            <label>{{ variant.name }}</label>
                            <nx:select 
                                v-model="selectedVariants[variant.id]"
                                :options="variant.options"
                                :placeholder="'Choisir ' + variant.name" />
                        </div>
                    @endforeach
                @endif
            </div>
            
            <div class="product-actions">
                <div class="quantity-selector">
                    <label>Quantité</label>
                    <nx:number-input 
                        v-model="quantity"
                        :min="1"
                        :max="product.stock" />
                </div>
                
                <div class="action-buttons">
                    <nx:button 
                        @click="addToCart"
                        :disabled="!canAddToCart"
                        :loading="addingToCart"
                        :variant="'primary'"
                        :size="'large'">
                        Ajouter au panier
                    </nx:button>
                    
                    <nx:button 
                        @click="toggleWishlist"
                        :variant="'outline'"
                        :icon="isInWishlist ? 'heart-filled' : 'heart'">
                        {{ isInWishlist ? 'Retirer' : 'Ajouter' }} aux favoris
                    </nx:button>
                </div>
            </div>
            
            <div class="product-description">
                <nx:tabs>
                    <nx:tab :title="'Description'">
                        <div v-html="product.description"></div>
                    </nx:tab>
                    
                    <nx:tab :title="'Caractéristiques'">
                        <nx:table :data="product.specifications" />
                    </nx:tab>
                    
                    <nx:tab :title="'Avis ({{ product.reviews_count }})'">
                        <ProductReviews :product-id="product.id" />
                    </nx:tab>
                </nx:tabs>
            </div>
        </div>
    </div>
    
    <div class="related-products">
        <h2>Produits similaires</h2>
        <nx:product-grid :products="relatedProducts" />
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useCart } from '@/composables/useCart';
import { useWishlist } from '@/composables/useWishlist';
import ProductReviews from '@/components/ProductReviews.nx';

export default {
    components: {
        ProductReviews
    },
    
    props: {
        product: {
            type: Object,
            required: true
        },
        relatedProducts: {
            type: Array,
            default: () => []
        }
    },
    
    setup(props) {
        const { addToCart: addItemToCart, isLoading: addingToCart } = useCart();
        const { isInWishlist, toggle: toggleWishlist } = useWishlist();
        
        const quantity = ref(1);
        const selectedVariants = reactive({});
        
        const canAddToCart = computed(() => {
            return props.product.stock > 0 && 
                   quantity.value <= props.product.stock &&
                   Object.keys(selectedVariants).length === props.product.variants.length;
        });
        
        const formatPrice = (price) => {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        };
        
        const calculateDiscount = () => {
            if (!props.product.sale_price) return 0;
            return Math.round(
                ((props.product.price - props.product.sale_price) / props.product.price) * 100
            );
        };
        
        const addToCart = async () => {
            await addItemToCart({
                product_id: props.product.id,
                quantity: quantity.value,
                variants: selectedVariants
            });
        };
        
        return {
            quantity,
            selectedVariants,
            canAddToCart,
            addingToCart,
            isInWishlist: isInWishlist(props.product.id),
            formatPrice,
            calculateDiscount,
            addToCart,
            toggleWishlist: () => toggleWishlist(props.product.id)
        };
    }
};
</script>

<style scoped>
.product-page {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.product-header {
    margin-bottom: 1.5rem;
}

.product-title {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.product-price {
    margin-bottom: 2rem;
}

.original-price {
    text-decoration: line-through;
    color: #6b7280;
    margin-right: 0.5rem;
}

.sale-price {
    font-size: 1.5rem;
    font-weight: bold;
    color: #ef4444;
    margin-right: 0.5rem;
}

.discount {
    background-color: #ef4444;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.current-price {
    font-size: 1.5rem;
    font-weight: bold;
    color: #1f2937;
}

.variant-group {
    margin-bottom: 1rem;
}

.variant-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.product-actions {
    margin: 2rem 0;
}

.quantity-selector {
    margin-bottom: 1rem;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.related-products {
    margin-top: 4rem;
    grid-column: 1 / -1;
}

@media (max-width: 768px) {
    .product-page {
        grid-template-columns: 1fr;
        gap: 2rem;
        padding: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>
```

---

## 🛠️ Outils de Développement

### Extension VSCode

L'extension **Nexa .nx Template Support** offre :

- **Coloration syntaxique** complète
- **Autocomplétion** intelligente
- **Snippets** de code
- **Validation** en temps réel
- **Prévisualisation** des composants
- **Navigation** rapide
- **Refactoring** assisté

### CLI Nexa

```bash
# Créer un nouveau composant
php nexa make:component UserProfile

# Générer une page complète
php nexa make:page ProductDetail

# Créer un layout
php nexa make:layout AdminLayout

# Compiler les templates
php nexa compile:templates

# Optimiser pour la production
php nexa optimize:templates

# Analyser les performances
php nexa analyze:templates
```

### DevTools

```javascript
// Activer les DevTools en développement
if (process.env.NODE_ENV === 'development') {
    window.__NEXA_DEVTOOLS__ = true;
}
```

### Hot Reload

```javascript
// Configuration du Hot Reload
export default {
    devServer: {
        hot: true,
        watchFiles: [
            'interface/**/*.nx',
            'workspace/**/*.php'
        ]
    }
};
```

---

## 🚀 Conclusion

Les templates `.nx` révolutionnent le développement web avec :

- **Syntaxe intuitive** et moderne
- **Réactivité native** sans configuration
- **Auto-découverte** des composants
- **Performance optimisée** avec compilation avancée
- **Écosystème complet** d'outils de développement

### Prochaines Étapes

1. **Installer** l'extension VSCode
2. **Créer** votre premier composant `.nx`
3. **Explorer** les exemples avancés
4. **Rejoindre** la communauté Discord
5. **Contribuer** au projet open source

### Ressources

- **Documentation** : [docs.nexa-framework.com](https://docs.nexa-framework.com)
- **Exemples** : [github.com/nexa/examples](https://github.com/nexa/examples)
- **Discord** : [discord.gg/nexa](https://discord.gg/nexa)
- **YouTube** : [Tutoriels vidéo](https://youtube.com/NexaFramework)

---

**Créez des interfaces modernes avec les templates .nx !** 🎨✨