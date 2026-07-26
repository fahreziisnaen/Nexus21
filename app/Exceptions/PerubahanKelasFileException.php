<?php

namespace App\Exceptions;

/**
 * Thrown by NaikKelasController::parsePerubahanKelasFile() for a malformed or
 * missing header row. Kept distinct from a bare \RuntimeException so
 * previewPerubahanKelas()'s catch block can safely show this exception's
 * message to the user without also matching \Illuminate\Database\QueryException
 * (which extends \PDOException, which extends \RuntimeException).
 */
class PerubahanKelasFileException extends \RuntimeException
{
}
