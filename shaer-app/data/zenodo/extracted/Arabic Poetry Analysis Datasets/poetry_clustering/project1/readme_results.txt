# About Dataset
This dataset contains the final clustering assigment of poems to clusters used for the paper "Clustering analysis of metered Arabic poetry compositions". There are 8 result files, one per retained option of two attributes and one summary file.

opt_1_pot_rep.csv: clustering option by the two metrics POT and REP

opt_2_rep_div.csv: clustering option by the two metrics REP and DIV

opt_3_rep_eve.csv: clustering option by the two metrics REP and EVE

opt_4_rep_var.csv: clustering option by the two metrics REP and VAR
opt_5_rep_var.csv: clustering option by the two metrics REP and VAR

opt_6_usg_eve.csv: clustering option by the two metrics USG and EVE

opt_7_usg_rep.csv: clustering option by the two metrics USG and REP
opt_8_usg_rep.csv: clustering option by the two metrics USG and REP

overall.xlsx: summary of all clustering assignments.


# About Data collection methodology
This dataset contains the final clustering results obtained by R experiments.

# Description of the data. More details are described in the article itself.
Column name: POETRY
Column description: poet and poem
Column data type: Categorical

Column name: USG
Column description: pattern usage
Column data type: Numeric

Column name: POT
Column description: pattern potential
Column data type: Numeric

Column name: REP
Column description: maximum repetition
Column data type: Numeric

Column name: DIV
Column description: Shannon Diversity Index
Column data type: Numeric

Column name: EVE
Column description: Shannon Evenness Index
Column data type: Numeric

Column name: VAR
Column description: Variability of syllabic quantity
Column data type: Numeric

Column name: cluster
Column description: cluster id assigned to the poem
Column data type: Numeric

# Files formats
These results are provided in comma separated value format (CSV). Files are encoded in UTF8.

# Online Repository
https://zenodo.org/doi/10.5281/zenodo.8256824

# Author
Abdelmalek Berkani, University of Neuchâtel, Switzerland

# Related paper
Abdelmalek Berkani and Adrian Holzer. Clustering analysis of metered Arabic poetry compositions. In 2023 IEEE/ACS 20th International Conference on Computer Systems and Applications (AICCSA), pages 1–8. IEEE, 2023.

