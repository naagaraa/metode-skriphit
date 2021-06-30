<?php
// load function matrix
require_once "../example/src/matrix/matrix.php";

/**
 * function menghitung normalisasi
 * @author eka jaya nagara
 * @param array
 * @return array
 */
function normalisasi_value( $matrix = [], $cost)
{
    $normalisasi = [];
    $cost = $cost; // index colum 0 adalah cost
    for ($i=0; $i < count($matrix) ; $i++) { 
        // check index colum yang termasuk cost
        if ($i == $cost) {
            foreach($matrix[$cost] as $key => $value){
                $normalisasi[$cost][$key] = round( $value / max($matrix[$cost]) , 3 );
            }
        }else{
            foreach($matrix[$i] as $key => $value){
                $normalisasi[$i][$key] = round( min($matrix[$i]) / $value , 3 );
            }
        }
    }
    return $normalisasi;
}

/**
 * function menghitung nilai alternative
 * @author eka jaya nagara
 * @param array
 * @return array
 */
function get_alternative($flip_matrix= [], $bobot = [])
{
    // check kondisi jumlah colum selalu sama dengan jumlah bobotnya
    $column = flip_matrix($flip_matrix);
    if (count($column) !== count($bobot)) {
        echo "jumlah column yang di inputkan tidak sama dengan jumlah nama column yang masukan";
        exit;
    }
    // flix matrix merubah row menjadi colum
    // bobot adalah nilai kritria / bobot
    $data = [];
    for ($i=0; $i < count($flip_matrix) ; $i++) { 
        foreach ($flip_matrix[$i] as $key => $value) {
            $data[$i][$key] = $flip_matrix[$i][$key] * $bobot[$key];
        }
    }

    return $data;
}

/**
 * function menghitung nilai vector dari jumlah alternative
 * @author eka jaya nagara
 * @param array
 * @return array
 */
function hitung_v($alternative = [])
{
    // menjulahlan row nilai alternativenya
    $result = [];
    for ($i=0; $i < count($alternative); $i++) { 
        foreach($alternative[$i] as $key => $value) {
            $result[$i] = array_sum($alternative[$i]);
        }
    }
    return $result;
}

/**
 * function menggabungkan nilai array vector ke dalam array original
 * @author eka jaya nagara
 * @param array
 * @return array
 */
function menambah_hasil_akhir_ke_dalam_field_data($data = [], $vector = [], $field = "")
{
    // mengabungkan nilai alternative dengan original arraynya
    $final = $data;
    for ($i=0; $i < count($final); $i++) { 
        foreach($final[$i] as $key => $value) {
            $final[$i][$field] = $vector[$i];
        }
    }
    return $final;
}

/**
 * function combinasi perhitungan SAW
 * @author eka jaya nagara
 * @param array interger dan string
 * @return array
 */
function saw($data_original = [], $jumlah_column_kriteria, $index_column_cost , $nama_column_kriteria = [], $bobot = [], $column_hasil = "hasil_akhir" )
{
    // buat new matrix
    $matrix = make_new_matrix($data_original, $jumlah_column_kriteria,$nama_column_kriteria, $index_column_cost );
    // normalisasi
    $normalisasi = normalisasi_value($matrix , $index_column_cost);
    // transform matrix
    $flip_matrix = flip_matrix($normalisasi);
    // hitung alternative
    $data_alternative = get_alternative($flip_matrix, $bobot);
    // hitung total dari data alternativenya
    $hasil_vector = hitung_v($data_alternative);
    // gabungkan dengan data originalnya dengan menambahkan satu column hasil
    $arr = menambah_hasil_akhir_ke_dalam_field_data($data_original, $hasil_vector, $column_hasil);

    return $arr;
}
