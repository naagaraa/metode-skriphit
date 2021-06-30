<?php

namespace MathPHP\Tests\Statistics\Multivariate;

use MathPHP\Functions\Map\Multi;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\SampleData;
use MathPHP\Statistics\Multivariate\PCA;
use MathPHP\Exception;

class PcaTestCenterTrueScaleTrueTest extends \PHPUnit\Framework\TestCase
{
    /** @var PCA */
    private static $pca;

    /** @var NumericMatrix  */
    private static $matrix;

    /**
     * R code for expected values:
     *   library(mdatools)
     *   data = mtcars[,c(1:7,10,11)]
     *   model = pca(data, center=TRUE, scale=TRUE)
     *
     * @throws Exception\MathException
     */
    public static function setUpBeforeClass(): void
    {
        $mtCars = new SampleData\MtCars();

        // Remove and categorical variables
        self::$matrix = MatrixFactory::create($mtCars->getData())->columnExclude(8)->columnExclude(7);
        self::$pca = new PCA(self::$matrix, true, true);
    }

    /**
     * @test The class returns the correct R-squared values
     *
     * R code for expected values:
     *   model$calres$expvar / 100
     */
    public function testRsq()
    {
        // Given
        $expected = [0.628437719, 0.231344477, 0.056023869, 0.029447503, 0.020350960, 0.013754799, 0.011673547, 0.006501528, 0.002465598];

        // When
        $R2 = self::$pca->getR2();

        // Then
        $this->assertEqualsWithDelta($expected, $R2, .00001);
    }

    /**
     * @test The class returns the correct cumulative R-squared values
     *
     * R code for expected values:
     *   model$calres$cumexpvar / 100
     */
    public function testCumRsq()
    {
        // Given
        $expected = [0.6284377, 0.8597822, 0.9158061, 0.9452536, 0.9656045, 0.9793593, 0.9910329, 0.9975344, 1.0000000];

        // When
        $cumR2 = self::$pca->getCumR2();

        // Then
        $this->assertEqualsWithDelta($expected, $cumR2, .00001);
    }

    /**
     * @test The class returns the correct loadings
     *
     * R code for expected values:
     *   model$loadings
     *
     * @throws \Exception
     */
    public function testLoadings()
    {
        // Given
        $expected = [
            [-0.3931477, 0.02753861, -0.22119309, -0.006126378, -0.320762, 0.72015586, -0.38138068, -0.12465987, 0.11492862],
            [0.4025537, 0.01570975, -0.25231615, 0.040700251, 0.1171397, 0.2243255, -0.15893251, 0.81032177, 0.16266295],
            [0.3973528, -0.08888469, -0.07825139, 0.339493732, -0.4867849, -0.01967516, -0.18233095, -0.06416707, -0.66190812],
            [0.3670814, 0.26941371, -0.01721159, 0.068300993, -0.2947317, 0.35394225, 0.69620751, -0.16573993, 0.25177306],
            [-0.3118165, 0.34165268, 0.14995507, 0.845658485, 0.1619259, -0.01536794, 0.04767957, 0.13505066, 0.03809096],
            [0.3734771, -0.17194306, 0.45373418, 0.191260029, -0.1874822, -0.08377237, -0.42777608, -0.19839375, 0.56918844],
            [-0.2243508, -0.48404435, 0.62812782, -0.030329127, -0.1482495, 0.2575294, 0.27622581, 0.3561335, -0.16873731],
            [-0.2094749, 0.55078264, 0.20658376, -0.282381831, -0.562486, -0.32298239, -0.08555707, 0.31636479, 0.04719694],
            [0.2445807, 0.4843131, 0.46412069, -0.214492216, 0.399782, 0.35706914, -0.2060421, -0.10832772, -0.32045892]
        ];

        // And since each column could be multiplied by -1, we will compare the two and adjust.
        $loadings   = self::$pca->getLoadings();
        $load_array = $loadings->getMatrix();

        // Get an array that's roughly ones and negative ones.
        $quotiant = Multi::divide($expected[1], $load_array[1]);

        // Convert to exactly one or negative one. Cannot be zero.
        $signum = \array_map(
            function ($x) {
                return $x <=> 0;
            },
            $quotiant
        );
        $sign_change = MatrixFactory::diagonal($signum);

        // Multiplying a sign change matrix on the right changes column signs.
        $sign_adjusted = $loadings->multiply($sign_change);

        // Then
        $this->assertEqualsWithDelta($expected, $sign_adjusted->getMatrix(), .00001);
    }

    /**
     * @test The class returns the correct scores
     *
     * R code for expected values:
     *   model$calres$scores
     *   new = matrix(c(1:9), 1, 9)
     *   result = predict(model, new)
     *   result$scores
     *
     * @throws \Exception
     */
    public function testScores()
    {
        // Given
        $expected = [
            [-0.66422351, 1.1734476, -0.20431724, -0.12601751, 0.75200784, -0.12506777, -0.42357334, -0.003259165, -0.167051112],
            [-0.63719807, 0.9769448, 0.11077779, -0.08567709, 0.65668822, -0.06619437, -0.44849307, 0.056643244, -0.071592094],
            [-2.29973601, -0.3265893, -0.21014955, -0.10862524, -0.07622329, -0.56693648, 0.38612406, -0.202035744, 0.11450503],
            [-0.2152967, -1.9768101, -0.32946822, -0.30806225, -0.24391787, 0.08382435, 0.03299362, -0.023714111, -0.145255757],
            [1.58697405, -0.8287285, -1.03299254, 0.14738418, -0.22270405, 0.18280435, -0.05793795, 0.152342587, -0.154646072],
            [0.04960512, -2.4466838, 0.11177774, -0.87154914, -0.12574876, -0.23043022, 0.22451528, 0.098663134, -0.004233901],
            [2.71439677, 0.3610529, -0.65206041, 0.09633337, 0.29674234, 0.27763557, 0.44227307, -0.306373481, -0.18698081],
            [-2.04370658, -0.8006412, 0.84898795, -0.27451338, -0.26307848, -0.19042527, -0.394164, -0.187088365, -0.01046133],
            [-2.29506729, -1.3056004, 1.9684845, 0.05055875, -0.45988113, 0.20443847, 0.53713423, 0.413455512, -0.169005773],
            [-0.38252133, 0.5811211, 0.88632274, 0.07026946, 0.45835852, -0.07984989, -0.26113412, 0.204105964, 0.110461785],
            [-0.36652708, 0.4121971, 1.1486095, 0.06150898, 0.48309076, -0.16066456, -0.07979514, 0.352641772, 0.027108266],
            [1.88466875, -0.7241198, -0.20604588, -0.21856675, 0.27996207, 0.17135058, -0.0891448, 0.092140434, 0.396034809],
            [1.67107231, -0.7144354, -0.32644071, -0.28933625, 0.28061777, 0.33682412, 0.03346598, 0.182323579, 0.196526577],
            [1.77692371, -0.8411687, -0.08557921, -0.28421711, 0.34961695, 0.13926264, 0.20632469, 0.295340402, 0.147796262],
            [3.64958983, -0.9480878, 0.88315862, 0.21645793, -0.34788247, -0.24002207, -0.31053111, -0.171865268, -0.251117818],
            [3.71033756, -0.8426945, 0.93230325, 0.34099021, -0.34260485, -0.22646211, -0.28589695, -0.239313268, -0.028994385],
            [3.331963, -0.4805609, 0.67061959, 0.65189724, -0.43940743, 0.3104575, -0.38304409, -0.359765688, 0.223097923],
            [-3.45236266, -0.4327074, -0.22604214, 0.10018032, -0.33470301, 0.57303421, -0.24650594, -0.066340528, 0.220271421],
            [-3.85477722, 0.7084152, -0.22670973, 1.19340342, 0.53954318, 0.37207104, -0.20055288, 0.087333576, -0.241702175],
            [-3.85488283, -0.3872111, -0.25488964, 0.21962306, -0.30372397, 0.83750899, -0.10186868, 0.104053562, 0.042833437],
            [-1.90375523, -1.5725638, 0.06620424, 0.07989679, 0.5012657, -0.07212137, 0.74680802, -0.408144457, -0.082722856],
            [1.80402354, -1.1340965, -1.00776416, -0.58796239, 0.09903732, -0.33920894, -0.14045443, 0.156086022, -0.050247532],
            [1.46483534, -0.9777629, -0.76680342, -0.03308788, 0.26871378, -0.31479492, 0.03753417, 0.370979414, -0.043466032],
            [2.60135738, 0.7649595, -0.4891514, 0.9524755, 0.53065965, 0.05970074, 0.38212238, -0.28961299, 0.08206984],
            [1.87424485, -0.9791561, -0.89787633, 0.22438738, -0.50770999, 0.20785973, -0.32709161, 0.027471038, -0.130958896],
            [-3.14830645, -0.2552569, -0.36230545, 0.06406082, 0.03361267, -0.0958673, 0.1035227, -0.020876499, 0.021084764],
            [-2.77939557, 1.6373369, -0.35969974, 0.3188654, -0.4325103, -0.69006515, -0.2631312, -0.105695694, 0.085027267],
            [-2.90895427, 1.3962368, -0.91635036, -0.90254314, -0.75861156, 0.05473409, -0.03491081, -0.236552376, -0.04634105],
            [1.54812696, 3.0206982, -0.51945216, 0.8656085, -0.86048411, -0.50704173, 0.37940892, 0.548070377, 0.053196712],
            [0.08049995, 2.8346567, 0.34481747, -1.14659658, 0.29944552, -0.08124583, -0.26924964, -0.123537656, -0.047915313],
            [2.96252801, 3.9993896, 0.70296512, -0.73000448, -0.22756074, 0.65580986, 0.49422807, -0.082329298, -0.053112079],
            [-1.90443632, 0.108419, 0.39906976, 0.31285789, 0.11738974, -0.48091826, 0.31102454, -0.315146031, 0.165790892],
        ];

        // And since each column could be multiplied by -1, we will compare the two and adjust.
        $scores = self::$pca->getScores();
        $score_array = $scores->getMatrix();

        // Get an array that's roughly ones and negative ones.
        $quotiant = Multi::divide($expected[1], $score_array[1]);

        // Convert to exactly one or negative one. Cannot be zero.
        $signum = \array_map(
            function ($x) {
                return $x <=> 0;
            },
            $quotiant
        );
        $signature = MatrixFactory::diagonal($signum);

        // Multiplying a sign change matrix on the right changes column signs.
        $sign_adjusted = $scores->multiply($signature);

        // Then
        $this->assertEqualsWithDelta($expected, $sign_adjusted->getMatrix(), .00001);

        // And Given
        $expected = MatrixFactory::create([[0.1257286, 7.899684, 2.327884, -0.366373, 1.284736, -5.869623, -3.59103, -1.97999, 1.738207]]);
        $sign_adjusted = $expected->multiply($signature);

        // When
        $scores = self::$pca->getScores(MatrixFactory::create([[1,2,3,4,5,6,7,8,9]]));

        // Then
        $this->assertEqualsWithDelta($sign_adjusted->getMatrix(), $scores->getMatrix(), .00001);
    }

    /**
     * @test The class returns the correct eigenvalues
     *
     * R code for expected values:
     *   model$eigenvals
     */
    public function testEigenvalues()
    {
        // Given
        $expected = [5.65593947, 2.08210029, 0.50421482, 0.26502753, 0.18315864, 0.12379319, 0.105061920, .05851375, 0.02219038];

        // When
        $eigenvalues = self::$pca->getEigenvalues()->getVector();

        // Then
        $this->assertEqualsWithDelta($expected, $eigenvalues, .00001);
    }

    /**
     * @test The class returns the correct critical T² distances
     *
     * R code for expected values:
     *   model$T2lim
     */
    public function testCriticalT2()
    {
        // Given
        $expected = [4.159615, 6.852714, 9.40913, 12.01948, 14.76453, 17.69939, 20.87304, 24.33584, 28.14389];

        // When
        $criticalT2 = self::$pca->getCriticalT2();

        // Then
        $this->assertEqualsWithDelta($expected, $criticalT2, .00001);
    }

    /**
     * @test The class returns the correct critical Q distances
     *
     * R code for expected values:
     *   model$Qlim
     */
    public function testCriticalQ()
    {
        // Given
        $expected = [9.799571, 3.054654, 1.785614, 1.200338, 0.7974437, 0.534007, 0.2584248, 0.08314212, 0];

        // When
        $criticalQ = self::$pca->getCriticalQ();

        // Then
        $this->assertEqualsWithDelta($expected, $criticalQ, .00001);
    }

    /**
     * @test The class returns the correct T² distances
     *
     * R code for expected values:
     *   model$calres$T2
     *
     * @throws \Exception
     */
    public function testGetT²Distances()
    {
        // Given
        $expected = [
            [0.0780052327, 0.7393467, 0.8221398, 0.8820597, 3.969633, 4.095989, 5.80369, 5.803872, 7.061447],
            [0.0717867274, 0.5301802, 0.5545185, 0.5822158, 2.936674, 2.97207, 4.886617, 4.94145, 5.172425],
            [0.9350852706, 0.9863127, 1.0739, 1.1184216, 1.150143, 3.746545, 5.16563, 5.863217, 6.454077],
            [0.008195397, 1.8850397, 2.1003236, 2.4584085, 2.783241, 2.840001, 2.850363, 2.859973, 3.810801],
            [0.4452817489, 0.7751366, 2.8914441, 2.9734058, 3.244193, 3.514139, 3.54609, 3.942719, 5.020456],
            [0.0004350591, 2.8755423, 2.9003219, 5.7664314, 5.852765, 6.281691, 6.761476, 6.927837, 6.928645],
            [1.3026924773, 1.365302, 2.2085592, 2.2435748, 2.724338, 3.347002, 5.208813, 6.812961, 8.388501],
            [0.7384691114, 1.046344, 2.4758548, 2.7601935, 3.138064, 3.430987, 4.909784, 5.507969, 5.512901],
            [0.9312924774, 1.7499814, 9.4350614, 9.4447064, 10.599392, 10.937012, 13.683137, 16.604595, 17.891772],
            [0.025870603, 0.1880634, 1.746066, 1.7646973, 2.91175, 2.963255, 3.61231, 4.324267, 4.874136],
            [0.023752393, 0.1053558, 2.7219067, 2.7361821, 4.01036, 4.218878, 4.279483, 6.404731, 6.437847],
            [0.6280081903, 0.879845, 0.9640451, 1.1442959, 1.572224, 1.809402, 1.885041, 2.030133, 9.098221],
            [0.4937256968, 0.7388714, 0.9502169, 1.2660915, 1.696027, 2.612478, 2.623139, 3.191242, 4.931757],
            [0.5582552432, 0.8980874, 0.9126126, 1.2174087, 1.884765, 2.04143, 2.446618, 3.93731, 4.921688],
            [2.3549590602, 2.7866724, 4.3335709, 4.5103602, 5.171111, 5.636489, 6.554324, 7.059123, 9.900902],
            [2.4340085101, 2.7750747, 4.498922, 4.9376475, 5.578502, 5.992783, 6.770772, 7.749531, 7.787415],
            [1.9628882995, 2.0738046, 2.9657471, 4.5692409, 5.623403, 6.401991, 7.798527, 10.010509, 12.253493],
            [2.1073082516, 2.1972346, 2.2985705, 2.3364386, 2.948073, 5.600628, 6.179002, 6.254217, 8.440727],
            [2.6272041022, 2.8682357, 2.9701711, 8.3439967, 9.933367, 11.051658, 11.434494, 11.564842, 14.197511],
            [2.6273480696, 2.6993583, 2.8282095, 3.0102068, 3.513859, 9.179932, 9.278705, 9.463741, 9.546421],
            [0.640792567, 1.8285149, 1.8372076, 1.8612938, 3.23315, 3.275168, 8.583677, 11.430562, 11.738942],
            [0.5754129681, 1.1931425, 3.2073408, 4.5117327, 4.565284, 5.494759, 5.682529, 6.09889, 6.21267],
            [0.379378631, 0.8385401, 2.0046849, 2.0088158, 2.403048, 3.203543, 3.216953, 5.568976, 5.654117],
            [1.196452022, 1.4774966, 1.9520346, 5.3751114, 6.912575, 6.941366, 8.331189, 9.764625, 10.068155],
            [0.621080505, 1.0815515, 2.6804372, 2.8704164, 4.277772, 4.626787, 5.645129, 5.658026, 6.430894],
            [1.752464565, 1.783758, 2.0440939, 2.0595783, 2.065747, 2.139988, 2.241994, 2.249442, 2.269476],
            [1.365827867, 2.6534085, 2.9100132, 3.2936532, 4.314982, 8.161639, 8.82066, 9.011582, 9.337382],
            [1.4961289803, 2.4324321, 4.0977897, 7.1713728, 10.313411, 10.337612, 10.349212, 11.305518, 11.402293],
            [0.4237487167, 4.8061589, 5.3413089, 8.1684797, 12.211057, 14.287837, 15.657993, 20.791506, 20.919034],
            [0.0011457411, 3.8603637, 4.0961741, 9.0567294, 9.546292, 9.599614, 10.289639, 10.550459, 10.653922],
            [1.5517443671, 9.2339474, 10.2140058, 12.224765, 12.507492, 15.981726, 18.306654, 18.422492, 18.549614],
            [0.6412511483, 0.6468967, 0.9627476, 1.3320679, 1.407305, 3.275602, 4.196356, 5.893684, 7.132357],
        ];

        // When
        $T²Distances = self::$pca->getT2Distances()->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $T²Distances, .00001);
    }

    /**
     * @test The class returns the correct T² distances
     *
     * R code for expected values:
     *   new = matrix(c(1:9), 1, 9)
     *   result = predict(model, new)
     *   result$T2
     *
     * @throws \Exception
     */
    public function testT2WithNewData()
    {
        // Given
        $expected = [[0.002794881, 29.97494, 40.72243, 41.2289, 50.24047, 328.5471, 451.289, 518.2879, 654.4443]];
        $newdata  = MatrixFactory::create([[1,2,3,4,5,6,7,8,9]]);

        // When
        $T²Distances = self::$pca->getT2Distances($newdata)->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $T²Distances, .0001);
    }

    /**
     * @test The class returns the correct Q residuals
     *
     * R code for expected values:
     *   model$calres$Q
     *
     * @throws \Exception
     */
    public function testGetQResiduals()
    {
        // Given
        $expected = [
            [2.2230939, 0.8461148, 0.80436922, 0.78848881, 0.22297302, 0.20733107, 0.0279166962, 0.02790607, 4.999714E-31],
            [1.6191345, 0.6647133, 0.65244159, 0.64510102, 0.21386161, 0.20947992, 0.008333885, 0.005125428, 6.842829E-31],
            [0.6928714, 0.5862109, 0.54204804, 0.53024859, 0.5244386, 0.20302164, 0.053929844, 0.0131114, 1.57464E-30],
            [4.2005024, 0.2927243, 0.18417497, 0.08927262, 0.0297767, 0.02275017, 0.0216615939, 0.02109923, 2.915298E-30],
            [1.9090817, 1.2222907, 0.1552171, 0.133495, 0.08389791, 0.05048048, 0.0471236715, 0.02391541, 1.932863E-30],
            [6.8874241, 0.9011625, 0.88866819, 0.12907029, 0.11325754, 0.06015945, 0.00975234, 0.00001792591, 2.53645E-30],
            [1.0543916, 0.9240324, 0.49884964, 0.48956953, 0.40151351, 0.324432, 0.1288265333, 0.03496182, 2.948984E-30],
            [1.7331133, 1.092087, 0.37130642, 0.29594882, 0.22673854, 0.19047676, 0.0351114957, 0.0001094394, 1.131099E-30],
            [6.3233872, 4.6187948, 0.74386353, 0.74130734, 0.52981669, 0.4880216, 0.199508412, 0.02856295, 3.754023E-30],
            [1.4667281, 1.1290264, 0.34345841, 0.33852062, 0.12842808, 0.12205208, 0.0538610506, 0.01220181, 7.749942E-31],
            [1.8836417, 1.7137353, 0.39443148, 0.39064813, 0.15727144, 0.13145834, 0.1250910774, 0.0007348581, 1.165573E-30],
            [0.8955959, 0.3712463, 0.32879143, 0.28102, 0.20264125, 0.17328022, 0.1653334295, 0.1568436, 1.146506E-30],
            [0.9658784, 0.4554604, 0.34889684, 0.26518138, 0.18643504, 0.07298456, 0.071864583, 0.0386227, 2.124879E-30],
            [1.0889335, 0.3813688, 0.37404503, 0.29326566, 0.17103365, 0.15163957, 0.1090696882, 0.02184374, 2.317664E-30],
            [2.0933538, 1.1944834, 0.41451424, 0.3676602, 0.24663799, 0.1890274, 0.0925978287, 0.06306016, 5.534352E-30],
            [2.0041095, 1.2939754, 0.42478607, 0.30851175, 0.19113367, 0.13984858, 0.0581115149, 0.0008406744, 4.42474E-30],
            [1.721029, 1.4900902, 1.04035958, 0.61538957, 0.42231067, 0.32592681, 0.1792040337, 0.04977268, 5.090618E-30],
            [0.8024469, 0.6152112, 0.56411615, 0.55408006, 0.44205395, 0.11368574, 0.0529205643, 0.0485195, 4.009016E-30],
            [2.5132733, 2.0114213, 1.96002398, 0.53581226, 0.24470541, 0.10626855, 0.066047095, 0.05841994, 4.686943E-30],
            [1.0798441, 0.9299116, 0.86494292, 0.81670863, 0.72446038, 0.02303907, 0.0126618472, 0.001834703, 8.184432E-30],
            [3.4713395, 0.9983825, 0.99399948, 0.98761599, 0.73634868, 0.73114719, 0.1734249687, 0.006843071, 2.761784E-30],
            [2.8189495, 1.5327746, 0.51718598, 0.1714862, 0.16167781, 0.04661511, 0.0268876607, 0.002524814, 2.742524E-30],
            [1.8573293, 0.9013091, 0.31332158, 0.31222677, 0.24001968, 0.14092384, 0.1395150215, 0.001889296, 2.143608E-30],
            [2.2534341, 1.6682712, 1.42900208, 0.5217925, 0.24019284, 0.23662866, 0.0906111425, 0.006735459, 3.858793E-30],
            [2.2411472, 1.2824005, 0.4762186, 0.4258689, 0.16809947, 0.12489381, 0.0179048904, 0.01715023, 3.575296E-30],
            [0.2224428, 0.1572867, 0.02602148, 0.02191769, 0.02078788, 0.01159734, 0.0008803955, 0.0004445673, 1.98756E-30],
            [3.6628254, 0.9819534, 0.85256946, 0.75089431, 0.56382916, 0.08763924, 0.0184012158, 0.007229636, 5.472723E-30],
            [4.2415698, 2.2920927, 1.45239473, 0.63781061, 0.0623191, 0.05932328, 0.0581045197, 0.002147493, 5.016662E-30],
            [11.5884126, 2.463795, 2.19396446, 1.44468638, 0.70425347, 0.44716216, 0.3032110286, 0.00282989, 3.386989E-30],
            [9.655183, 1.6199041, 1.50100503, 0.1863213, 0.09665368, 0.0900528, 0.0175574295, 0.002295877, 5.777982E-30],
            [17.7579146, 1.7627973, 1.26863739, 0.73573085, 0.68394696, 0.25386039, 0.0095990062, 0.002820893, 9.523531E-30],
            [0.737494, 0.7257394, 0.56648268, 0.46860262, 0.45482227, 0.22353991, 0.1268036408, 0.02748662, 2.271057E-30],
        ];

        // When
        $qResiduals = self::$pca->getQResiduals()->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $qResiduals, .00001);
    }

    /**
     * @test The class returns the correct Q residuals
     *
     * R code for expected values:
     *   new = matrix(c(1:9), 1, 9)
     *   result = predict(model, new)
     *   result$Q
     *
     * @throws \Exception
     */
    public function testQWithNewData()
    {
        // Given
        $expected = [[123.8985, 61.49351, 56.07446, 55.94023, 54.28968, 19.83721, 6.941721, 3.021362, 6.86309e-29]];
        $newData  = MatrixFactory::create([[1,2,3,4,5,6,7,8,9]]);

        // When
        $qResiduals = self::$pca->getQResiduals($newData)->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $qResiduals, .0001);
    }
}
