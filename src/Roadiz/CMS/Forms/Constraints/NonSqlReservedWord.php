<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NonSqlReservedWord.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

class NonSqlReservedWord extends Constraint
{
    /**
     * List of forbidden field names.
     *
     * These are SQL reserved words.
     *
     * @var array
     */
    public static $forbiddenNames = [
        'title', 'id', 'translation', 'node', 'urlAliases', 'url_aliases', 'documentsByFields',
        'publishedAt', 'published_at', 'published at', 'documents_by_fields',
        'metaTitle', 'metaKeywords', 'metaDescription', 'order', 'integer', 'int', 'float', 'join',
        'inner', 'select', 'from', 'where', 'by', 'varchar',
        'text', 'enum', 'left', 'outer', 'blob', 'accessible',
        'add', 'all', 'alter', 'analyze', 'and', 'as', 'asc',
        'asensitive', 'before', 'between', 'bigint', 'binary',
        'blob', 'both', 'by', 'call', 'cascade', 'case', 'change',
        'char', 'character', 'check', 'collate', 'column', 'condition',
        'constraint', 'continue', 'convert', 'create', 'cross',
        'current_date', 'current_time', 'current_timestamp',
        'current_user', 'cursor', 'database', 'databases',
        'day_hour', 'day_microsecond', 'day_minute', 'day_second',
        'dec', 'decimal', 'declare', 'default', 'delayed', 'delete',
        'desc', 'describe', 'deterministic', 'distinct', 'distinctrow',
        'div', 'double', 'drop', 'dual', 'each', 'else', 'elseif',
        'enclosed', 'escaped', 'exists', 'exit', 'explain', 'false',
        'fetch', 'float', 'float4', 'float8', 'for', 'force', 'foreign',
        'from', 'fulltext', 'get', 'grant', 'group', 'having',
        'high_priority', 'hour_microsecond', 'hour_minute',
        'hour_second', 'if', 'ignore', 'in', 'index', 'infile', 'inner',
        'inout', 'insensitive', 'insert', 'int', 'int1', 'int2', 'int3',
        'int4', 'int8', 'integer', 'interval', 'into', 'io_after_gtids',
        'io_before_gtids', 'is', 'iterate', 'join', 'key', 'keys', 'kill',
        'leading', 'leave', 'left', 'like', 'limit', 'linear', 'lines',
        'load', 'localtime', 'localtimestamp', 'lock', 'long', 'longblob',
        'longtext', 'loop', 'low_priority', 'master_bind', 'master_ssl_verify_server_cert',
        'match', 'maxvalue', 'mediumblob', 'mediumint', 'mediumtext',
        'middleint', 'minute_microsecond', 'minute_second', 'mod', 'modifies',
        'natural', 'not', 'no_write_to_binlog', 'null', 'numeric', 'on',
        'optimize', 'option', 'optionally', 'or', 'order', 'out', 'outer',
        'outfile', 'partition', 'precision', 'primary', 'procedure', 'purge',
        'range', 'read', 'reads', 'read_write', 'real', 'references', 'regexp',
        'release', 'rename', 'repeat', 'replace', 'require', 'resignal',
        'restrict', 'return', 'revoke', 'right', 'rlike', 'schema', 'schemas',
        'second_microsecond', 'select', 'sensitive', 'separator', 'set',
        'show', 'signal', 'smallint', 'spatial', 'specific', 'sql',
        'sqlexception', 'sqlstate', 'sqlwarning', 'sql_big_result',
        'sql_calc_found_rows', 'sql_small_result', 'ssl', 'starting',
        'straight_join', 'table', 'terminated', 'then', 'tinyblob',
        'tinyint', 'tinytext', 'to', 'trailing', 'trigger', 'true',
        'undo', 'union', 'unique', 'unlock', 'unsigned', 'update', 'usage',
        'use', 'using', 'utc_date', 'utc_time', 'utc_timestamp', 'values',
        'varbinary', 'varchar', 'varcharacter', 'varying', 'when', 'where',
        'while', 'with', 'write', 'xor', 'year_month', 'zerofill',
    ];

    public $message = 'string.should.not.be.a.sql.reserved.word';
}
