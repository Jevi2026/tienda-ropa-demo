# Hilo: demo de tienda de ropa

Tienda en línea con panel de administración para un negocio de ropa pequeño o mediano. Guarda la ropa en un inventario, recibe pedidos, controla qué se pagó y qué sigue pendiente, y saca el corte de caja al final del día.

Es una demo: la marca "Hilo", los productos, los clientes y los pedidos son ficticios.

**Hecho con:** Laravel 12, PHP 8.2 y Blade, sin compilación de frontend. Usa SQLite en local y se puede pasar a Supabase (Postgres) cambiando el `.env`.

## Qué puede hacer

### Tienda pública

| Función | Detalle |
|---|---|
| Portada y catálogo | Búsqueda, categorías y prendas destacadas |
| Ficha de producto | Tallas, aviso de últimas unidades y tallas agotadas bloqueadas |
| Carrito y pedido | Sin registro. El cliente elige retiro o envío y cómo va a pagar |
| Comprobante del pedido | Enlace privado y botón para avisar a la tienda por WhatsApp |
| Celular | Todas las pantallas funcionan en teléfono |

### Panel de administración (`/admin`)

| Necesidad | Dónde está | Qué hace |
|---|---|---|
| Guardar ropa nueva | Inventario → Nueva prenda | Foto, precio, costo, categoría, tallas y existencias |
| Saber cuánto hay | Inventario | Existencias por talla, valor a costo y a precio de venta, alerta de poco inventario |
| Ver qué pasó con una prenda | Inventario → editar prenda | Historial de entradas, ajustes, ventas y devoluciones |
| Vender en mostrador | Pedidos → Nueva venta | Descuenta inventario y cobra todo, una parte o nada |
| Ver los pedidos | Pedidos | Filtros por Pendiente, Abonado, Pagado y Cancelado, y búsqueda por número, nombre o teléfono |
| Controlar pagos | Detalle del pedido | Abonos y varios métodos de pago, con el saldo siempre a la vista |
| Sacar la caja del día | Caja | Personas que compraron, pedidos, cobros por método, gastos y diferencia contra el efectivo contado |
| Ver el negocio de un vistazo | Resumen | Cobros de 7 días, cuentas por cobrar y poco inventario |

## Cómo se calcula la caja

- **El dinero cuenta el día en que entra.** Un pedido del lunes pagado el martes suma a la caja del martes.
- **Efectivo esperado** = apertura + cobros en efectivo + otros ingresos − gastos y retiros.
- **La diferencia** compara ese número con lo que se contó y lo reporta como sobrante o faltante.
- **Personas que compraron** son compradores distintos del día, identificados por teléfono o nombre. Cada venta de mostrador sin datos cuenta como una persona.
- **Al cerrar** se guarda una foto del día. Si después cambia un pedido viejo, ese cierre no se altera.
- **Con la caja cerrada** no entran pagos ni gastos hasta reabrirla. Solo se puede reabrir el último cierre.
- **Cancelar un pedido** devuelve la ropa al inventario y registra la devolución del dinero que se había cobrado.

## Instalación

Necesitas PHP 8.2 o superior y [Composer](https://getcomposer.org/).

**En Windows con XAMPP**, abre `C:\xampp\php\php.ini` y quita el `;` de esta línea. Sin ella, Composer no puede instalar los paquetes:

```ini
extension=zip
```

Luego, en la carpeta del proyecto:

```bash
composer setup
```

```bash
php artisan serve
```

Abre <http://127.0.0.1:8000>.

> `composer setup` **borra la base de datos** y la llena otra vez con los datos de ejemplo. Sirve para instalar o reiniciar la demo. No lo uses donde haya datos reales.

### Acceso al panel de prueba

- URL: <http://127.0.0.1:8000/admin>
- Correo: `admin@demo.test`
- Contraseña: `demo1234`

Son credenciales solo para uso local. Cámbialas con `ADMIN_EMAIL` y `ADMIN_PASSWORD` en el `.env` antes de publicar.

## Recorrido para enseñar la demo (5 minutos)

1. Abre la tienda y compra una prenda desde el celular.
2. Entra a **Pedidos**: el pedido ya está ahí como Pendiente.
3. Registra un abono y luego el resto: el estado pasa a Abonado y después a Pagado.
4. Abre **Inventario**: la talla vendida ya bajó.
5. Registra una venta de mostrador en **Nueva venta**.
6. Abre **Caja**, escribe el efectivo contado y mira la diferencia antes de cerrar.

## Dónde se guardan los datos

| Opción | ¿Sirve? | Cuándo usarla |
|---|---|---|
| **SQLite** | Sí, viene configurada | Para la demo o para probar en tu computadora |
| **Supabase** | Sí, es la recomendada para clientes reales | Laravel se conecta directo porque es Postgres. Todas las pantallas siguen igual |
| **Firebase** | No se recomienda aquí | Firestore no es una base relacional: Eloquent y las transacciones de pedidos, inventario y caja tendrían que reescribirse |

Una página solo de presentación no necesita base de datos. Esta sí, porque guarda inventario, pedidos y caja.

### Conectar Supabase

> Este camino aún no se ha probado contra un proyecto real de Supabase. Las pruebas automáticas corren en SQLite, así que revisa todo el flujo después de conectarlo.

1. Crea un proyecto en Supabase y abre **Connect** para copiar los datos del **Session pooler**. Es la opción por IPv4; la conexión directa usa IPv6 si no tienes el complemento de IPv4.
2. En XAMPP, activa estas líneas en `php.ini`:

   ```ini
   extension=pdo_pgsql
   extension=pgsql
   ```

3. Cambia el `.env` con los valores de tu proyecto:

   ```env
   DB_CONNECTION=pgsql
   DB_HOST=el-host-que-muestra-supabase.pooler.supabase.com
   DB_PORT=5432
   DB_DATABASE=postgres
   DB_USERNAME=postgres.TU_PROJECT_REF
   DB_PASSWORD=tu-contraseña
   DB_SSLMODE=require
   ```

4. Crea las tablas:

   ```bash
   php artisan migrate --force
   ```

5. **Apaga la Data API de Supabase** (Integrations → Data API → Enable Data API). Laravel no la usa, y las tablas creadas por migraciones no traen Row Level Security activado. Si la dejas encendida, los datos de clientes podrían quedar expuestos por esa API.

Referencias: [conectarse a Postgres](https://supabase.com/docs/guides/database/connecting-to-postgres) y [asegurar la Data API](https://supabase.com/docs/guides/api/securing-your-api).

## Adaptarla a un cliente

| Qué cambiar | Dónde |
|---|---|
| Nombre de la tienda | `APP_NAME` en `.env` |
| Moneda y decimales | `TIENDA_MONEDA` y `TIENDA_DECIMALES` |
| WhatsApp de la tienda | `TIENDA_WHATSAPP`, con código de país (`504XXXXXXXX`) |
| Código de país de los clientes | `TIENDA_CODIGO_PAIS` |
| Zona horaria (define qué es "hoy" en la caja) | `APP_TIMEZONE` |
| Métodos de pago | `config/tienda.php` |
| Colores, tipografía y formas | Variables al inicio de `public/css/app.css` |
| Quitar la banda de demostración | `TIENDA_DEMO=false` |

## Antes de publicarla en internet

- [ ] `APP_ENV=production` y `APP_DEBUG=false`
- [ ] `APP_URL` con el dominio real, servido por HTTPS
- [ ] `TIENDA_DEMO=false`
- [ ] Sin datos de ejemplo: no ejecutes `composer setup` ni `db:seed` tal cual, porque cargan productos y ventas ficticios
- [ ] Usuario de administración con contraseña propia
- [ ] Fotos propias de la tienda
- [ ] Copias de seguridad de la base de datos
- [ ] Almacenamiento que conserve las fotos subidas (`php artisan storage:link`). En servidores que borran los archivos al desplegar, usa un disco externo

## Límites actuales

- **No cobra en línea.** El pedido queda pendiente y la tienda confirma el pago por fuera.
- **No emite factura fiscal.** Antes de reemplazar el proceso de facturación de un negocio en Honduras, hay que revisar con su contador los requisitos del SAR.
- **Un solo tipo de usuario.** Quien entra al panel puede hacer todo.
- **Una sola sucursal.** La caja y el inventario son únicos.
- **Las fotos de ejemplo** vienen de Unsplash por enlace, así que necesitan internet. Son solo para la demo.
- **Los pedidos web apartan inventario** desde que se crean. Si el cliente no paga, hay que cancelar el pedido para liberar las prendas.

## Qué se le puede agregar

- Pagos con tarjeta en línea
- Roles (dueño y vendedor) y bitácora de quién hizo qué
- Varias sucursales con traslados de inventario
- Clientes frecuentes, historial de compra y cuentas por cobrar por persona
- Aviso automático por WhatsApp cuando cambia el estado de un pedido
- Cancelación automática de pedidos sin pagar después de cierto tiempo
- Exportar caja e inventario a Excel, y reportes de lo más vendido
- Códigos de barras y lector en mostrador
- Cupones, descuentos y variantes por color

## Pruebas

```bash
composer test
```

Las pruebas cubren compra e inventario, pagos y abonos, cancelaciones, cierre y reapertura de caja, acceso al panel y validación de fotos.
