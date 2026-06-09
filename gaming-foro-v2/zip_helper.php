<?php
/**
 * NEXUS — Helper de ZIP robusto
 * ------------------------------------------------------------------
 * Permite crear y leer archivos ZIP AUNQUE la extensión `zip`
 * (ZipArchive) esté desactivada en PHP. Si ZipArchive está disponible
 * se usa (con compresión); si no, se recurre a una implementación en
 * PHP puro con método "store" (sin compresión) para crear, y a un
 * lector que entiende "store" y "deflate" (vía gzinflate) para leer.
 *
 * Así los backups de la web funcionan sin tocar la configuración del
 * servidor ni reiniciar Apache.
 */

/**
 * Crea un ZIP en disco.
 *
 * @param array  $files   Mapa  'ruta/en/zip' => '/ruta/real/en/disco'
 * @param array  $strings Mapa  'ruta/en/zip' => 'contenido en texto'
 * @param string $tmpFile Ruta de salida del ZIP
 * @return bool  true si se creó correctamente
 */
function nexus_make_zip(array $files, array $strings, string $tmpFile): bool
{ // Inicio de la función que genera el archivo ZIP
    // ── Camino preferente: extensión ZipArchive (comprime) ──
    if (class_exists('ZipArchive')) { // Si la extensión zip está disponible...
        $zip = new ZipArchive(); // Crea el objeto ZipArchive
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false; // Abre/crea el ZIP (o falla)
        foreach ($files as $name => $real) { // Recorre los archivos de disco
            if (is_file($real)) $zip->addFile($real, $name); // Añade el archivo si existe
        } // Fin del bucle de archivos
        foreach ($strings as $name => $data) { // Recorre los contenidos en memoria
            $zip->addFromString($name, $data); // Añade el contenido como archivo del ZIP
        } // Fin del bucle de cadenas
        return $zip->close(); // Cierra y finaliza el ZIP (devuelve true/false)
    } // Fin del camino ZipArchive

    // ── Fallback en PHP puro (método "store", sin compresión) ──
    $local   = '';   // Secuencia de cabeceras locales + datos
    $central = '';   // Directorio central
    $offset  = 0;    // Desplazamiento acumulado
    $count   = 0;    // Nº de entradas

    // Función interna que añade una entrada (nombre + datos crudos)
    $put = function (string $name, string $data) use (&$local, &$central, &$offset, &$count) { // Closure por referencia
        $crc  = crc32($data);          // Suma de control de los datos
        $len  = strlen($data);         // Tamaño (store: comprimido == sin comprimir)
        $nlen = strlen($name);         // Longitud del nombre

        // Cabecera local (firma PK\x03\x04)
        $lh  = "\x50\x4b\x03\x04";      // Firma de cabecera local
        $lh .= pack('v', 20);          // Versión mínima necesaria
        $lh .= pack('v', 0x0800);      // Flags: nombre en UTF-8
        $lh .= pack('v', 0);           // Método 0 = store (sin compresión)
        $lh .= pack('v', 0);           // Hora de modificación (0)
        $lh .= pack('v', 0x21);        // Fecha de modificación (1980-01-01)
        $lh .= pack('V', $crc);        // CRC32
        $lh .= pack('V', $len);        // Tamaño comprimido
        $lh .= pack('V', $len);        // Tamaño sin comprimir
        $lh .= pack('v', $nlen);       // Longitud del nombre
        $lh .= pack('v', 0);           // Longitud del campo extra
        $lh .= $name;                  // Nombre de la entrada
        $local .= $lh . $data;         // Cabecera + datos al bloque local

        // Registro en el directorio central (firma PK\x01\x02)
        $cd  = "\x50\x4b\x01\x02";     // Firma del registro de directorio central
        $cd .= pack('v', 20);          // Versión "made by"
        $cd .= pack('v', 20);          // Versión mínima necesaria
        $cd .= pack('v', 0x0800);      // Flags: UTF-8
        $cd .= pack('v', 0);           // Método store
        $cd .= pack('v', 0);           // Hora
        $cd .= pack('v', 0x21);        // Fecha
        $cd .= pack('V', $crc);        // CRC32
        $cd .= pack('V', $len);        // Tamaño comprimido
        $cd .= pack('V', $len);        // Tamaño sin comprimir
        $cd .= pack('v', $nlen);       // Longitud del nombre
        $cd .= pack('v', 0);           // Longitud extra
        $cd .= pack('v', 0);           // Longitud del comentario
        $cd .= pack('v', 0);           // Nº de disco
        $cd .= pack('v', 0);           // Atributos internos
        $cd .= pack('V', 0);           // Atributos externos
        $cd .= pack('V', $offset);     // Desplazamiento a la cabecera local
        $cd .= $name;                  // Nombre
        $central .= $cd;               // Añade el registro al directorio central

        $offset += strlen($lh) + $len; // Avanza el desplazamiento acumulado
        $count++;                      // Una entrada más
    }; // Fin de la closure $put

    foreach ($files as $name => $real) {            // Recorre los archivos de disco
        $data = @file_get_contents($real);          // Lee su contenido
        if ($data === false) continue;              // Salta los ilegibles
        $put($name, $data);                         // Añade la entrada al ZIP
    } // Fin del bucle de archivos
    foreach ($strings as $name => $data) {          // Recorre los contenidos en memoria
        $put($name, $data);                         // Añade cada uno como entrada
    } // Fin del bucle de cadenas

    // Fin del directorio central (firma PK\x05\x06)
    $eocd  = "\x50\x4b\x05\x06";             // Firma "End Of Central Directory"
    $eocd .= pack('v', 0);                  // Nº de disco
    $eocd .= pack('v', 0);                  // Disco del directorio central
    $eocd .= pack('v', $count);             // Entradas en este disco
    $eocd .= pack('v', $count);             // Entradas totales
    $eocd .= pack('V', strlen($central));   // Tamaño del directorio central
    $eocd .= pack('V', strlen($local));     // Desplazamiento del directorio central
    $eocd .= pack('v', 0);                  // Longitud del comentario

    return file_put_contents($tmpFile, $local . $central . $eocd) !== false; // Escribe el ZIP completo a disco
} // Fin de nexus_make_zip

/**
 * Lee un ZIP y devuelve sus entradas (nombre => contenido).
 * Soporta "store" (método 0) y "deflate" (método 8, vía gzinflate).
 *
 * @param string $zipPath Ruta del ZIP a leer
 * @return array|false  Mapa nombre => contenido, o false si no se pudo leer
 */
function nexus_read_zip(string $zipPath)
{ // Inicio de la función que lee un archivo ZIP
    // ── Camino preferente: ZipArchive ──
    if (class_exists('ZipArchive')) { // Si la extensión zip está disponible...
        $zip = new ZipArchive(); // Crea el objeto ZipArchive
        if ($zip->open($zipPath) !== true) return false; // Abre el ZIP (o falla)
        $out = []; // Acumulador de entradas leídas
        for ($i = 0; $i < $zip->numFiles; $i++) { // Recorre cada entrada del ZIP
            $name = $zip->getNameIndex($i); // Nombre/ruta de la entrada
            if ($name === false || substr($name, -1) === '/') continue; // Salta errores y carpetas
            $out[$name] = $zip->getFromIndex($i); // Guarda nombre => contenido
        } // Fin del bucle de entradas
        $zip->close(); // Cierra el ZIP
        return $out; // Devuelve el mapa de entradas
    } // Fin del camino ZipArchive

    // ── Fallback en PHP puro: parsea el directorio central ──
    $bin = @file_get_contents($zipPath); // Lee el ZIP entero como binario
    if ($bin === false) return false; // Falla si no se pudo leer

    // Localiza el End Of Central Directory (firma PK\x05\x06) desde el final
    $eocdPos = strrpos($bin, "\x50\x4b\x05\x06"); // Posición del EOCD (última aparición)
    if ($eocdPos === false) return false; // Si no hay EOCD, no es un ZIP válido

    $cdCount  = unpack('v', substr($bin, $eocdPos + 10, 2))[1]; // Nº de entradas totales
    $cdOffset = unpack('V', substr($bin, $eocdPos + 16, 4))[1]; // Desplazamiento al directorio central

    $out = []; // Acumulador de entradas leídas
    $p   = $cdOffset; // Puntero al registro actual del directorio central
    for ($i = 0; $i < $cdCount; $i++) { // Recorre cada registro del directorio central
        if (substr($bin, $p, 4) !== "\x50\x4b\x01\x02") break; // Para si el registro no es válido

        $method   = unpack('v', substr($bin, $p + 10, 2))[1]; // Método de compresión
        $compSize = unpack('V', substr($bin, $p + 20, 4))[1]; // Tamaño comprimido
        $nameLen  = unpack('v', substr($bin, $p + 28, 2))[1]; // Longitud del nombre
        $extraLen = unpack('v', substr($bin, $p + 30, 2))[1]; // Longitud extra (central)
        $commLen  = unpack('v', substr($bin, $p + 32, 2))[1]; // Longitud del comentario
        $lhOffset = unpack('V', substr($bin, $p + 42, 4))[1]; // Desplazamiento a la cabecera local
        $name     = substr($bin, $p + 46, $nameLen);          // Nombre de la entrada

        // En la cabecera local, los campos nombre/extra pueden diferir → releer
        $lhNameLen  = unpack('v', substr($bin, $lhOffset + 26, 2))[1]; // Longitud nombre (local)
        $lhExtraLen = unpack('v', substr($bin, $lhOffset + 28, 2))[1]; // Longitud extra (local)
        $dataStart  = $lhOffset + 30 + $lhNameLen + $lhExtraLen;        // Inicio de los datos
        $raw        = substr($bin, $dataStart, $compSize);             // Datos comprimidos crudos

        if (substr($name, -1) !== '/') {            // Ignora carpetas
            if ($method === 0) {                    // Método store (sin compresión)
                $out[$name] = $raw;                 // El contenido es directamente los datos
            } elseif ($method === 8 && function_exists('gzinflate')) { // Método deflate
                $out[$name] = gzinflate($raw);      // Descomprime con gzinflate
            } // Otros métodos no soportados se omiten
        } // Fin del filtro de carpetas

        $p += 46 + $nameLen + $extraLen + $commLen; // Avanza al siguiente registro del directorio central
    } // Fin del bucle del directorio central

    return $out; // Devuelve el mapa nombre => contenido
} // Fin de nexus_read_zip
