set(LIBDMX485_VERSION 0.0.6)
set(LIBDMX485_SO_VERSION 0)

cmake_minimum_required(VERSION 2.6)
project(LIBDMX485)

add_library(dmx485 SHARED libdmx485.c)
target_link_libraries(dmx485 rt)
set_target_properties(dmx485 PROPERTIES VERSION ${LIBDMX485_VERSION} SOVERSION ${LIBDMX485_SO_VERSION})

add_executable(dmx485out dmx485out.c)

target_link_libraries(dmx485out dmx485)

install(FILES libdmx485.h DESTINATION include)
install(TARGETS dmx485 LIBRARY DESTINATION lib)
install(TARGETS dmx485out DESTINATION bin)
