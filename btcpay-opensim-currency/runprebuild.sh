#!/bin/sh
    dotnet ../bin/prebuild.dll /target vs2022 /targetframework net8_0 /excludedir = "obj | bin" /file prebuild.xml
    echo "dotnet build -c Release OpenSim.Currency.sln" > compile.sh
    chmod +x compile.sh

