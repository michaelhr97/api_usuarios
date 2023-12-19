# Memoria

Aspectos a tener en cuenta:

- La práctica se ha desarrollado en un portatil con el sistema operativo linux
- Para probar los endpoints se ha utilizado la herramienta [Postman](https://www.postman.com/) en vez de en `localhost:8000/api-docs`.

## Práctica

Para poder ejecutar cualquier endpoint, se ha de proporcionar un token, en caso contrario, se devolverá un error de autenticación.

En concreto, los endpoints desarrollados son: `GET`, `CGET`, `POST`, `DELETE`, `PUT` y `DELETE`. La ruta base de estos endpoints es `/api/v1/results`.

### Endpoints GET y CGET

Para estos endpoints se ha de tener en cuenta:

- Si el usuario tiene rol `ROLE_ADMIN`, podrá obtener cualquier resultado de cualquier usuario.
- Si el usuario tiene rol `ROLE_USER`, únicamente podrá obtener sus propios resultados. En caso de intentar obtener un resultado de otro usuario, recibirá un mensaje de error.

### Endpoint POST

Para este se ha de tener en cuenta:

- Si el usuario tiene rol `ROLE_ADMIN`, podrá crear un resultado para cualquier usuario.
- Si el usuario tiene rol `ROLE_USER`, únicamente podrá crear un resultado para el mismo. En caso de que se intente crear un resultado para cualquier otro resultado, recibirá un mensaje de error.

### Endpoint PUT

Para este se ha de tener en cuenta:

- Si el usuario tiene rol `ROLE_ADMIN`, podrá modificar un resultado para cualquier usuario.
- Si el usuario tiene rol `ROLE_USER`, únicamente podrá modificar un resultado si es suyo. En caso de que se intente crear un resultado para cualquier otro resultado, recibirá un mensaje de error.

### Endpoint DELETE

Para este se ha de tener en cuenta:

- Si el usuario tiene rol `ROLE_ADMIN`, podrá eliminar un resultado para cualquier usuario.
- Si el usuario tiene rol `ROLE_USER`, únicamente podrá eliminar un resultado si es suyo. En caso de que se intente crear un resultado para cualquier otro resultado, recibirá un mensaje de error.
